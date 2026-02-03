<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Structure extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = [];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
