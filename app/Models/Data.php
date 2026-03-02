<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    protected $table = 'data';

    protected $fillable = ['name', 'raw_data', 'digital_data'];

    protected $casts = [
        'raw_data' => 'array',
        'digital_data' => 'array',
    ];
}
