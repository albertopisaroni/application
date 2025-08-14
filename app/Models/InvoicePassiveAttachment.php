<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InvoicePassiveAttachment extends Model
{
    protected $fillable = [
        'invoice_passive_id', 'filename', 'mime_type', 'file_extension', 'file_size',
        'file_hash', 's3_path', 's3_url', 'is_encrypted', 'attachment_type',
        'description', 'metadata', 'is_primary', 'is_processed',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_encrypted' => 'boolean',
        'metadata' => 'array',
        'is_primary' => 'boolean',
        'is_processed' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoicePassive::class, 'invoice_passive_id');
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopePdf($query)
    {
        return $query->where('attachment_type', 'pdf');
    }

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    // Accessor per dimensione file formattata
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Metodi per scaricare/visualizzare il file
    public function getFileContent(): string
    {
        $content = Storage::disk('s3')->get($this->s3_path);
        
        if ($this->is_encrypted) {
            $content = decrypt($content);
        }
        
        return $content;
    }

    public function getDownloadResponse()
    {
        $content = $this->getFileContent();
        
        return response($content)
            ->header('Content-Type', $this->mime_type)
            ->header('Content-Disposition', 'attachment; filename="' . $this->filename . '"');
    }

    public function getViewResponse()
    {
        $content = $this->getFileContent();
        
        return response($content)
            ->header('Content-Type', $this->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $this->filename . '"');
    }
}
