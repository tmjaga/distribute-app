<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TarfinLabs\LaravelSpatial\Casts\LocationCast;
use TarfinLabs\LaravelSpatial\Traits\HasSpatial;

class Restaurant extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = ['title', 'coordinates', 'orders_count', 'icon_color'];

    protected $casts = [
        'coordinates' => LocationCast::class,
    ];

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }
}
