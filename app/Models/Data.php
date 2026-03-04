<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Data extends Model
{
    protected $table = 'data';

    protected $fillable = ['user_id', 'name', 'raw_data', 'digital_data'];

    protected $casts = [
        'raw_data' => 'array',
        'digital_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tableRows(): HasMany
    {
        return $this->hasMany(DataTableRow::class, 'data_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Sync data_table_rows from digital_data (content is JSON string with headers + rows). Idempotent: replaces all rows.
     */
    public function syncTableRowsFromDigitalData(): void
    {
        $digital = $this->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? null) !== 'table') {
            return;
        }
        $decoded = json_decode($digital['content'] ?? '', true);
        if (! is_array($decoded)) {
            return;
        }
        $rows = $decoded['rows'] ?? [];
        if (! is_array($rows)) {
            return;
        }
        $this->tableRows()->delete();
        foreach ($rows as $i => $row) {
            $cells = is_array($row) ? $row : [];
            $searchContent = implode(' ', array_map(fn ($v) => (string) $v, $cells));
            $this->tableRows()->create([
                'row_index' => $i,
                'search_content' => $searchContent,
                'cells' => $cells,
            ]);
        }
    }

    /**
     * Rebuild digital_data content (rows only) from current data_table_rows (e.g. after edit/delete).
     */
    public function rebuildDigitalDataRowsFromTableRows(): void
    {
        $digital = $this->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? null) !== 'table') {
            return;
        }
        $decoded = json_decode($digital['content'] ?? '{}', true) ?: [];
        $ordered = $this->tableRows()->orderBy('row_index')->get();
        $decoded['rows'] = $ordered->pluck('cells')->all();
        $digital['content'] = json_encode($decoded);
        $this->update(['digital_data' => $digital]);
    }
}
