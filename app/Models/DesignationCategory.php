<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignationCategory extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function designations()
    {
        return $this->hasMany(Designation::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
