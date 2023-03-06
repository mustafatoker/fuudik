<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Services\CreateOrder;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/v1')]
class CreateOrderAction extends Controller
{
    public function __construct(private readonly CreateOrder $createOrder)
    {
        // ...
    }

    #[Post(uri: 'orders')]
    public function __invoke(CreateOrderRequest $request): JsonResponse
    {
        try {
            $this->createOrder->handle($request);

            return response()->json([
                'message' => 'Order created successfully',
            ], Response::HTTP_CREATED);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            Log::info("Error while creating the order: {$exception->getMessage()}");

            return response()->json([
                'message' => 'Something went wrong while creating the order',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
