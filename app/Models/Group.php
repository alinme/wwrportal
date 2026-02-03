<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = [];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }
}
