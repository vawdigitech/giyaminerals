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

    /**
     * Get the work location name for this entry (if assigned to a task)
     */
    public function getWorkLocationNameAttribute()
    {
        if (!$this->task_id) {
            return '-';
        }

        // Find the Stock record for this entry's location and product
        $stock = \App\Models\Stock::where('product_id', $this->product_id)
            ->where('location_type', $this->location_type)
            ->where('location_id', $this->location_id)
            ->first();

        if (!$stock) {
            return '-';
        }

        // Find TaskStockUsage with this stock_id and task_id
        $taskUsage = \App\Models\TaskStockUsage::where('task_id', $this->task_id)
            ->where('stock_id', $stock->id)
            ->where('notes', 'like', '%Auto-created from stock entry%')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$taskUsage || !$taskUsage->location_type || !$taskUsage->location_id) {
            return '-';
        }

        if ($taskUsage->location_type === 'site') {
            $location = \App\Models\Site::find($taskUsage->location_id);
            return $location ? 'SITE: ' . $location->name : '-';
        } elseif ($taskUsage->location_type === 'factory') {
            $location = \App\Models\Factory::find($taskUsage->location_id);
            return $location ? 'FACTORY: ' . $location->name : '-';
        }

        return '-';
    }

    public function getLocationNameAttribute() {
    return $this->location_type === 'warehouse'
      ? \App\Models\Warehouse::find($this->location_id)?->name
      : \App\Models\Site::find($this->location_id)?->name;
  }

}