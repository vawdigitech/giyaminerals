<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLocation extends Model
{
    protected $fillable = [
        'project_id',
        'site_id',
        'factory_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    /**
     * Get the location type (site or factory)
     */
    public function getLocationType(): string
    {
        return $this->site_id ? 'site' : 'factory';
    }

    /**
     * Get the location model (site or factory)
     */
    public function getLocation()
    {
        return $this->site_id ? $this->site : $this->factory;
    }

    /**
     * Validation: Ensure either site_id or factory_id is set (not both, not neither)
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($projectLocation) {
            $hasSite = !is_null($projectLocation->site_id);
            $hasFactory = !is_null($projectLocation->factory_id);

            if (!$hasSite && !$hasFactory) {
                throw new \Exception('Project location must have either a site or a factory.');
            }

            if ($hasSite && $hasFactory) {
                throw new \Exception('Project location cannot have both a site and a factory.');
            }
        });
    }
}
