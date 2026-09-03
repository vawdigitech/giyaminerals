<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Warehouse;
use App\Models\Site;

class Transfer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'task_id',
        'task_stock_usage_id',
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'quantity',
        'transfer_date',
        'transfer_type',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'transfer_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function getFromNameAttribute()
    {
        if ($this->from_type === 'warehouse') {
            return Warehouse::find($this->from_id)?->name ?? 'Unknown Warehouse';
        } else {
            return Site::find($this->from_id)?->name ?? 'Unknown Site';
        }
    }

    public function getToNameAttribute()
    {
        if ($this->to_type === 'warehouse') {
            return Warehouse::find($this->to_id)?->name ?? 'Unknown Warehouse';
        } else {
            return Site::find($this->to_id)?->name ?? 'Unknown Site';
        }
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function taskStockUsage()
    {
        return $this->belongsTo(TaskStockUsage::class, 'task_stock_usage_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fromSite()
    {
        return $this->belongsTo(Site::class, 'from_id');
    }

    public function toSite()
    {
        return $this->belongsTo(Site::class, 'to_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_id');
    }
}
