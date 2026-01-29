<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskStockUsage extends Model
{
    protected $fillable = [
        'task_id',
        'product_id',
        'stock_id',
        'quantity',
        'unit_price',
        'total_cost',
        'notes',
        'used_by',
        'used_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($usage) {
            // Calculate total cost
            $usage->total_cost = $usage->quantity * $usage->unit_price;
        });

        static::created(function ($usage) {
            // Deduct from stock (increase transferred_quantity)
            $stock = $usage->stock;
            $stock->transferred_quantity += $usage->quantity;
            $stock->save();

            // Recalculate task costs
            $usage->task->recalculateCosts();
        });

        static::deleted(function ($usage) {
            // Restore stock (decrease transferred_quantity)
            $stock = $usage->stock;
            $stock->transferred_quantity -= $usage->quantity;
            $stock->save();

            // Recalculate task costs
            $usage->task->recalculateCosts();
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
