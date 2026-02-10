<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockEntry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['product_id','location_type','location_id','task_id','quantity','entry_date','reference','created_by'];
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function user() { return $this->belongsTo(User::class, 'created_by'); }

    public function task() { return $this->belongsTo(Task::class); }

    public function getLocationNameAttribute() {
    return $this->location_type === 'warehouse'
      ? \App\Models\Warehouse::find($this->location_id)?->name
      : \App\Models\Site::find($this->location_id)?->name;
  }

}