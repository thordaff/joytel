<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_code',
        'name',
        'category',
        'region',
        'data_amount_mb',
        'price',
        'description',
        'system_type',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2'
    ];

    /**
     * Get formatted data amount (e.g., "1GB", "500MB")
     */
    public function getFormattedDataAmountAttribute(): string
    {
        if ($this->data_amount_mb >= 1024) {
            return round($this->data_amount_mb / 1024, 1) . 'GB';
        }
        
        return $this->data_amount_mb . 'MB';
    }

    /**
     * Get display name with data amount
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ' ' . $this->formatted_data_amount;
    }

    /**
     * Scope for active products only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by system type
     */
    public function scopeForSystem($query, string $systemType)
    {
        return $query->where('system_type', $systemType);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}