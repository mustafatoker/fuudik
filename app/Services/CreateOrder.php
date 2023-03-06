<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Jobs\SendLowStockNotification;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateOrder
{
    public function handle(Request $request): void
    {
        $productsFromRequest = $request->collect('products');

        $productIds = $productsFromRequest->pluck('product_id')->all();

        try {
            DB::transaction(fn() => $this->createOrder($productIds, $productsFromRequest), 3);
        } catch (RuntimeException $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }

    private function createOrder(array $productIds, Collection $productsFromRequest): void
    {
        /** @var Order $order */
        $order = Order::query()->create([
            'status' => OrderStatus::PENDING,
        ]);

        /** @var EloquentCollection<Product> $products */
        $products = Product::query()
            ->with(['ingredients'])
            ->whereIn('id', $productIds)
            ->get();

        $ingredientsToBeUpdated = collect();
        $products->each(function (Product $product) use ($order, $productsFromRequest, &$ingredientsToBeUpdated) {
            $foundProductInRequest = $this->findProductInRequest($productsFromRequest, $product);

            $ingredientsToBeUpdated = $ingredientsToBeUpdated->merge(
                $this->processIngredientsOf($product, $foundProductInRequest['quantity'])
            );

            $order->attachProduct($product, $foundProductInRequest['quantity']);
        });

        $this->bulkUpdateStockOfIngredients($ingredientsToBeUpdated);

        $ingredientIdsWhichAreReachedToLowStock = $ingredientsToBeUpdated
            ->filter(fn(array $ingredient): bool => $ingredient['has_low_stock'])
            ->pluck('id');

        $this->sendLowStockNotificationForIngredients($ingredientIdsWhichAreReachedToLowStock);
    }

    private function findProductInRequest(Collection $productsFromRequest, Product $productModel): array
    {
        return $productsFromRequest
            ->filter(fn(array $product): bool => $product['product_id'] === $productModel->id)
            ->firstOrFail();
    }

    private function processIngredientsOf(Product $productModel, int $quantityOfRequestedForProduct): Collection
    {
        $ingredientsToBeUpdated = collect();
        $productModel->ingredients()->each(function (Ingredient $ingredient) use ($quantityOfRequestedForProduct, $ingredientsToBeUpdated): void {
            $requestedTotalRecipeQuantity = $this->getRequestedTotalRecipeQuantity(
                $quantityOfRequestedForProduct, $ingredient->recipe_quantity);

            if (!$ingredient->hasEnoughStockBy($requestedTotalRecipeQuantity)) {
                throw ValidationException::withMessages([
                    'products' => "Not enough stock for the ingredient [{$ingredient->name}]",
                ]);
            }

            $ingredientsToBeUpdated->push([
                'id' => $ingredient->id,
                'stock' => $ingredient->stock,
                'requestedTotalRecipeQuantity' => $requestedTotalRecipeQuantity,
                'has_low_stock' => $ingredient->shouldNotifyLowStock($requestedTotalRecipeQuantity),
            ]);
        });

        return $ingredientsToBeUpdated;
    }

    private function getRequestedTotalRecipeQuantity(int $quantity, int $ingredientRecipeQuantity): int
    {
        return $quantity * $ingredientRecipeQuantity;
    }

    private function bulkUpdateStockOfIngredients(Collection $ingredientsToBeUpdated): void
    {
        if ($ingredientsToBeUpdated->isEmpty()) {
            return;
        }

        $ingredientCases = collect();
        $ingredientIds = collect();
        $ingredientParams = collect();

        $ingredientsToBeUpdated->map(function (array $ingredient) use ($ingredientCases, $ingredientIds, $ingredientParams): void {
            $ingredientCases->push("WHEN {$ingredient['id']} then ?");
            $ingredientIds->push($ingredient['id']);
            $ingredientParams->push($ingredient['stock'] - $ingredient['requestedTotalRecipeQuantity']);
        });

        $ingredientIds = implode(',', $ingredientIds->all());
        $ingredientCases = implode(' ', $ingredientCases->all());

        DB::update("UPDATE ingredients SET `stock` = CASE `id` {$ingredientCases} END WHERE `id` in ({$ingredientIds})", $ingredientParams->all());
    }

    /**
     * @param Collection<int[]> $ingredientIdsWhichAreReachedToLowStock
     */
    private function sendLowStockNotificationForIngredients(Collection $ingredientIdsWhichAreReachedToLowStock): void
    {
        if ($ingredientIdsWhichAreReachedToLowStock->isEmpty()) {
            return;
        }

        SendLowStockNotification::dispatch($ingredientIdsWhichAreReachedToLowStock->all())
            ->afterCommit();
    }
}
