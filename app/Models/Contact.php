<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->organization ? "{$this->name} ({$this->organization})" : $this->name;
        }
        if ($this->organization) {
            return $this->organization;
        }

        return $this->email ?? $this->phone ?? (string) $this->id;
    }
}
