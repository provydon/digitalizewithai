<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedDataChart extends Model
{
    protected $table = 'saved_data_charts';

    protected $fillable = ['data_id', 'user_id', 'name', 'chart_config'];

    protected $casts = [
        'chart_config' => 'array',
    ];

    public function data(): BelongsTo
    {
        return $this->belongsTo(Data::class, 'data_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
