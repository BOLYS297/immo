<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    protected $fillable = [
        'last_name',
        'first_name',
        'email',
        'phone',
        'company',
    ];

    public function quoteRequests()
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.$this->last_name);
    }
}
