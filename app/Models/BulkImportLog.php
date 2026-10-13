<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'import_type',
        'file_name',
        'file_size_kb',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'status',
        'error_log',
    ];

    protected $casts = [
        'error_log' => 'array', // Maps directly to PostgreSQL jsonb
        'file_size_kb' => 'integer',
        'total_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
