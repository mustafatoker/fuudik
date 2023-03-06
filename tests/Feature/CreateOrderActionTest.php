<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\SendLowStockNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateOrderActionTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->runDatabaseMigrations();
        $this->seed();

        $this->withHeaders([
            'Accept' => 'application/json',
        ]);
    }

    public function test_order_can_be_created_when_provided_valid_data(): void
    {
        $json = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->post('/api/v1/orders', $json);

        $response->assertStatus(201);
    }

    public function test_order_should_send_low_stock_notification(): void
    {
        Bus::fake();

        $requestPayload = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 26,
                ],
                [
                    'product_id' => 2,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->post('/api/v1/orders', $requestPayload);

        $response->assertStatus(201);

        DB::table('ingredients')
            ->whereNull('last_stock_notification_reminded_at')
            ->update(['last_stock_notification_reminded_at' => now()]);

        $requestPayload2 = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                ],
                [
                    'product_id' => 2,
                    'quantity' => 1,
                ],
            ],
        ];

        $response2 = $this->json('POST', '/api/v1/orders', $requestPayload2);

        Bus::assertDispatchedTimes(SendLowStockNotification::class, 1);

        $response2->assertStatus(201);
    }

    public function test_order_cannot_be_created_when_provided_not_valid_stock_ingredient(): void
    {
        $json = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 1000,
                ],
            ],
        ];

        $response = $this->post('/api/v1/orders', $json);

        $response->assertSeeText('Not enough stock for the ingredient');
        $response->assertStatus(422);
    }

    public function test_order_can_not_be_created_when_provided_invalid_quantity(): void
    {
        $json = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 0,
                ],
            ],
        ];

        $response = $this->json('POST', '/api/v1/orders', $json);

        $response->assertStatus(422);
    }
}
