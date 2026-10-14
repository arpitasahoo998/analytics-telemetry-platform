<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';
    protected $guarded = [];

    protected $casts = [
        'properties' => 'array',
        'context' => 'array',
        'timestamp_epoch' => 'integer',
    ];
}
