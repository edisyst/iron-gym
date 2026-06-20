<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'member_id',
        'template_id',
        'channel',
        'subject',
        'body',
        'status',
        'sent_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<CommunicationTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class);
    }
}
