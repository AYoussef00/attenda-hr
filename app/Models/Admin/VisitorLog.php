<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'country_code',
        'country_name',
        'city',
        'user_agent',
        'path',
        'referrer',
    ];
}


