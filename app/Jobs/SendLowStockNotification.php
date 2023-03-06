<?php

namespace App\Jobs;

use App\Models\Ingredient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLowStockNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param array<int> $ingredientIds
     */
    public function __construct(private readonly array $ingredientIds)
    {
        Log::info('SendLowStockNotification job created', [$ingredientIds]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Ingredient::query()
            ->whereIn('id', $this->ingredientIds)
            ->whereNull('last_stock_notification_reminded_at')
            ->get()
            ->each(fn(Ingredient $ingredient) => $this->sendMail($ingredient));
    }

    private function sendMail(Ingredient $ingredient): void
    {
        Log::info("Stock is low for ingredient {$ingredient->name}, stock: {$ingredient->stock}. A mail is being sent");

//        Mail::send('emails.low-stock', ['ingredient' => $ingredient], function ($message) use ($ingredient) {
//            $message->to('...');
//            $message->subject("Stock is low for ingredient {$ingredient->name}.");
//        });

        $ingredient->last_stock_notification_reminded_at = now();
        $ingredient->save();
    }
}
