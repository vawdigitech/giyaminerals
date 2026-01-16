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

    protected static function booted()
    {
        static::saving(function ($stock) {
            $stock->balance = $stock->received_quantity - $stock->transferred_quantity;
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
