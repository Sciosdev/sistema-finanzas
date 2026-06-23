<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeleteSnapshot extends Model
{
    protected $table = 'finance_delete_snapshots';

    protected $fillable = [
        'user_id',
        'token',
        'entity_type',
        'table_name',
        'entity_id',
        'payload',
        'relations_payload',
        'expires_at',
        'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'relations_payload' => 'array',
            'expires_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
