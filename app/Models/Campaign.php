<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleting(function (Campaign $campaign) {
            $campaign->schools()->each(function (School $school) {
                $school->delete();
            });
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'target_kits' => 'integer',
        ];
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
