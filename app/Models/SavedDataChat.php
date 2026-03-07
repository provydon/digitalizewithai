<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedDataChat extends Model
{
    protected $table = 'saved_data_chats';

    protected $fillable = ['data_id', 'user_id', 'name', 'messages'];

    protected $casts = [
        'messages' => 'array',
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
