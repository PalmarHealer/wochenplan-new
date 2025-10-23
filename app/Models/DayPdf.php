<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayPdf extends Model
{
    protected $fillable = [
        'date',
        'pdf_content',
        'is_outdated',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_outdated' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the decoded PDF content
     */
    public function getDecodedPdfContent(): string
    {
        return base64_decode($this->pdf_content);
    }

    /**
     * Set PDF content from binary data
     */
    public function setPdfContentFromBinary(string $binaryData): void
    {
        $this->pdf_content = base64_encode($binaryData);
    }
}
