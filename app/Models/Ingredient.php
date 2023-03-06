<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property string $name
 * @property int $stock
 * @property \stdClass $pivot
 * @property Carbon $last_stock_notification_reminded_at
 * @property int $initial_stock
 * @property int $recipe_quantity
 * @property int $id
 */
class Ingredient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'stock',
        'initial_stock',
        'unit',
        'last_stock_notification_reminded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stock' => 'int',
        'initial_stock' => 'int',
        'unit' => ProductUnit::class,
        'last_stock_notification_reminded_at' => 'datetime',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function getRecipeQuantityAttribute(): int
    {
        return $this->pivot->quantity;
    }

    public function isStockLow(int $deductibleStock = 0): bool
    {
        $stock = $this->stock;
        if ($deductibleStock) {
            $stock = ($this->stock - $deductibleStock);
        }

        return $stock <= ($this->initial_stock / 2);
    }

    public function loadNewStock(int $newStock): void
    {
        $this->stock = $newStock;
        $this->last_stock_notification_reminded_at = now();
        $this->save();
    }

    public function hasEnoughStockBy(int $requestedTotalRecipeQuantity): bool
    {
        return $this->stock >= $requestedTotalRecipeQuantity;
    }

    public function shouldNotifyLowStock(int $deductibleStock = 0): bool
    {
        return $this->isStockLow($deductibleStock) && $this->last_stock_notification_reminded_at === null;
    }
}
