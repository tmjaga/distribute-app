<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TarfinLabs\LaravelSpatial\Casts\LocationCast;
use TarfinLabs\LaravelSpatial\Traits\HasSpatial;

class Driver extends Model
{
    /** @use HasFactory<\Database\Factories\DriverFactory> */
    use HasFactory, HasSpatial;

    protected $fillable = ['name', 'current_coordinates', 'capacity', 'restaurant_id', 'icon_color'];

    protected $casts = [
        'current_coordinates' => LocationCast::class,
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
