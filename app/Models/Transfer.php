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
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'quantity',
        'transfer_date'
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
}
