<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class School extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = [];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function structures(): HasMany
    {
        return $this->hasMany(Structure::class);
    }

    public function groups(): HasManyThrough
    {
        return $this->hasManyThrough(Group::class, Structure::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
