<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'location_type',
        'location_id',
        'received_quantity',
        'transferred_quantity',
        'balance',
        'last_updated_at'
    ];

    protected $casts = [
        'received_quantity' => 'decimal:3',
        'transferred_quantity' => 'decimal:3',
        'balance' => 'decimal:3',
        'last_updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($stock) {
            $stock->balance = round(
                (float) $stock->received_quantity - (float) $stock->transferred_quantity,
                3
            );
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'location_id');
    }

    public function location()
    {
        return $this->morphTo(__FUNCTION__, 'location_type', 'location_id');
    }
}
