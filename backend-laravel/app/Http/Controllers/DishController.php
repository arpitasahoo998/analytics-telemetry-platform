<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DishController extends Controller
{
    /**
     * Get list of menu dishes from MySQL database.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dish::where('is_available', true)->orderBy('display_order')->orderBy('id');

        if ($category = $request->query('category')) {
            if ($category !== 'all') {
                $query->where('category', strtolower(trim($category)));
            }
        }

        if ($request->has('is_veg')) {
            $isVeg = filter_var($request->query('is_veg'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_veg', $isVeg);
        }

        $dishes = $query->get()->map(function (Dish $dish) {
            return $dish->toFrontendArray();
        });

        return response()->json([
            'status' => 'success',
            'count' => $dishes->count(),
            'data' => $dishes,
        ])->header('Connection', 'close');
    }

    /**
     * Get a specific dish by dish_id or primary ID.
     */
    public function show(string $dishId): JsonResponse
    {
        $dish = Dish::where('dish_id', $dishId)->orWhere('id', $dishId)->first();

        if (!$dish) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dish not found',
            ], 404)->header('Connection', 'close');
        }

        return response()->json([
            'status' => 'success',
            'data' => $dish->toFrontendArray(),
        ])->header('Connection', 'close');
    }

    /**
     * Add a new dish dynamically to the MySQL database.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:indian,mexican,italian,chinese',
            'description' => 'required|string',
            'price' => 'required|numeric|min:1',
            'image' => 'required|string',
            'dish_id' => 'nullable|string|unique:dishes,dish_id',
            'badge' => 'nullable|string|max:64',
            'spice_level' => 'nullable|string|in:Mild,Medium,Spicy',
            'is_dessert' => 'nullable|boolean',
            'sweetness' => 'nullable|string',
            'is_veg' => 'nullable|boolean',
            'portion_size' => 'nullable|string',
            'prep_time' => 'nullable|string',
            'calories' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'allergens' => 'nullable|array',
        ]);

        if (empty($validated['dish_id'])) {
            $prefix = substr($validated['category'], 0, 3);
            $validated['dish_id'] = 'dish-' . $prefix . '-' . bin2hex(random_bytes(3));
        }

        $dish = Dish::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Dish created successfully',
            'data' => $dish->toFrontendArray(),
        ], 201)->header('Connection', 'close');
    }

    /**
     * Update an existing dish.
     */
    public function update(Request $request, string $dishId): JsonResponse
    {
        $dish = Dish::where('dish_id', $dishId)->orWhere('id', $dishId)->first();

        if (!$dish) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dish not found',
            ], 404)->header('Connection', 'close');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|in:indian,mexican,italian,chinese',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:1',
            'image' => 'sometimes|string',
            'badge' => 'nullable|string|max:64',
            'spice_level' => 'nullable|string',
            'is_dessert' => 'nullable|boolean',
            'sweetness' => 'nullable|string',
            'is_veg' => 'nullable|boolean',
            'portion_size' => 'nullable|string',
            'prep_time' => 'nullable|string',
            'calories' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'allergens' => 'nullable|array',
            'is_available' => 'nullable|boolean',
        ]);

        $dish->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Dish updated successfully',
            'data' => $dish->toFrontendArray(),
        ])->header('Connection', 'close');
    }

    /**
     * Delete or deactivate dish.
     */
    public function destroy(string $dishId): JsonResponse
    {
        $dish = Dish::where('dish_id', $dishId)->orWhere('id', $dishId)->first();

        if (!$dish) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dish not found',
            ], 404)->header('Connection', 'close');
        }

        $dish->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Dish removed from menu',
        ])->header('Connection', 'close');
    }
}
