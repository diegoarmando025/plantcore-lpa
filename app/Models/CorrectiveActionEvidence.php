<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectiveActionEvidence extends Model
{
    protected $fillable = [
        'corrective_action_id', 'uploaded_by', 'disk', 'path',
        'original_name', 'mime_type', 'size', 'note',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class, 'corrective_action_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
