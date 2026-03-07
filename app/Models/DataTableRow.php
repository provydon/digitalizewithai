<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataTableRow extends Model
{
    protected $table = 'data_table_rows';

    protected $fillable = ['data_id', 'row_index', 'search_content', 'cells'];

    protected $casts = [
        'cells' => 'array',
    ];

    public function data(): BelongsTo
    {
        return $this->belongsTo(Data::class, 'data_id');
    }

    /**
     * Scope: search by term. Uses pgSearch on PostgreSQL, else LIKE on search_content.
     */
    public function scopeSearch($query, ?string $term)
    {
        $trimmed = $term !== null ? trim($term) : '';
        if ($trimmed === '') {
            return $query;
        }
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return $query->pgSearch($trimmed, ['search_content']);
        }

        return $query->where('search_content', 'like', '%'.addcslashes($trimmed, '%_\\').'%');
    }
}
