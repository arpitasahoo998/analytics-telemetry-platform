<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    use HasFactory;

    protected $table = 'dishes';

    protected $fillable = [
        'dish_id',
        'name',
        'category',
        'description',
        'price',
        'image',
        'badge',
        'spice_level',
        'is_dessert',
        'sweetness',
        'is_veg',
        'portion_size',
        'prep_time',
        'calories',
        'ingredients',
        'allergens',
        'is_available',
        'display_order',
    ];

    protected $casts = [
        'price' => 'float',
        'is_dessert' => 'boolean',
        'is_veg' => 'boolean',
        'is_available' => 'boolean',
        'ingredients' => 'array',
        'allergens' => 'array',
        'display_order' => 'integer',
    ];

    /**
     * Map model to frontend camelCase format.
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->dish_id,
            'db_id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'price' => (float)$this->price,
            'image' => $this->image,
            'badge' => $this->badge,
            'spiceLevel' => $this->spice_level,
            'isDessert' => (bool)$this->is_dessert,
            'sweetness' => $this->sweetness,
            'isVeg' => (bool)$this->is_veg,
            'portionSize' => $this->portion_size,
            'prepTime' => $this->prep_time,
            'calories' => $this->calories,
            'ingredients' => $this->ingredients ?? [],
            'allergens' => $this->allergens ?? [],
            'isAvailable' => (bool)$this->is_available,
        ];
    }
}
