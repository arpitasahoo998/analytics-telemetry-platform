<?php

namespace Tests\Feature;

use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DishApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DishSeeder::class);
    }

    public function test_dishes_index_returns_all_dishes_from_database(): void
    {
        $response = $this->getJson('/api/dishes');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'count' => 16,
            ])
            ->assertJsonStructure([
                'status',
                'count',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'category',
                        'description',
                        'price',
                        'image',
                        'isVeg',
                    ],
                ],
            ]);
    }

    public function test_dishes_index_filters_by_category(): void
    {
        $response = $this->getJson('/api/dishes?category=indian');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'count' => 4,
            ]);

        $data = $response->json('data');
        foreach ($data as $dish) {
            $this->assertEquals('indian', $dish['category']);
        }
    }

    public function test_dishes_show_returns_single_dish(): void
    {
        $response = $this->getJson('/api/dishes/dish-ind-1');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => 'dish-ind-1',
                    'name' => 'Butter Chicken & Garlic Naan',
                    'category' => 'indian',
                ],
            ]);
    }

    public function test_dishes_store_creates_dish_dynamically(): void
    {
        $payload = [
            'name' => 'Kashmiri Rogan Josh',
            'category' => 'indian',
            'description' => 'Slow braised tender mutton in Kashmiri dried red chili gravy.',
            'price' => 450,
            'image' => '/images/dishes/rogan-josh.jpg',
            'badge' => 'New Special',
            'spice_level' => 'Spicy',
            'is_veg' => false,
            'portion_size' => 'Serves 1-2 • 450g',
        ];

        $response = $this->postJson('/api/dishes', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'Kashmiri Rogan Josh',
                    'category' => 'indian',
                    'price' => 450,
                ],
            ]);

        $this->assertDatabaseHas('dishes', [
            'name' => 'Kashmiri Rogan Josh',
        ]);
    }

    public function test_dishes_update_modifies_dish(): void
    {
        $dish = Dish::first();

        $response = $this->putJson("/api/dishes/{$dish->dish_id}", [
            'price' => 599.00,
            'badge' => 'Exclusive',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'price' => 599.00,
                    'badge' => 'Exclusive',
                ],
            ]);

        $this->assertDatabaseHas('dishes', [
            'id' => $dish->id,
            'price' => 599.00,
            'badge' => 'Exclusive',
        ]);
    }

    public function test_dishes_destroy_deletes_dish(): void
    {
        $dish = Dish::first();

        $response = $this->deleteJson("/api/dishes/{$dish->dish_id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseMissing('dishes', [
            'id' => $dish->id,
        ]);
    }
}
