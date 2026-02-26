<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_tid',
        'order_code',
        'product_code',
        'quantity',
        'status',
        'sn_pin',
        'qrcode',
        'cid',
        'request_data',
        'response_data',
        'system_type',
        'submitted_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'quantity' => 'integer',
    ];

    /**
     * Order status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * System type constants
     */
    const SYSTEM_WAREHOUSE = 'warehouse';
    const SYSTEM_RSP = 'rsp';

    /**
     * Generate unique order TID
     */
    public static function generateOrderTid(): string
    {
        do {
            $orderTid = 'JT' . date('YmdHis') . strtoupper(Str::random(6));
        } while (self::where('order_tid', $orderTid)->exists());
        
        return $orderTid;
    }

    /**
     * Scope for filtering by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by system type
     */
    public function scopeSystemType($query, $systemType)
    {
        return $query->where('system_type', $systemType);
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if order is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if order is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if order has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark order as completed
     */
    public function markAsCompleted(?array $responseData = null): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        
        if ($responseData) {
            $this->response_data = array_merge($this->response_data ?? [], $responseData);
        }
        
        return $this->save();
    }

    /**
     * Mark order as failed
     */
    public function markAsFailed(?string $errorMessage = null, ?array $responseData = null): bool
    {
        $this->status = self::STATUS_FAILED;
        
        if ($errorMessage || $responseData) {
            $failureData = $this->response_data ?? [];
            if ($errorMessage) {
                $failureData['error_message'] = $errorMessage;
            }
            if ($responseData) {
                $failureData = array_merge($failureData, $responseData);
            }
            $this->response_data = $failureData;
        }
        
        return $this->save();
    }

    /**
     * Mark order as processing
     */
    public function markAsProcessing(): bool
    {
        $this->status = self::STATUS_PROCESSING;
        return $this->save();
    }

    /**
     * Set SN/PIN data
     */
    public function setSnPin(?string $snPin): bool
    {
        $this->sn_pin = $snPin;
        return $this->save();
    }

    /**
     * Set QR code data
     */
    public function setQrCode(?string $qrcode): bool
    {
        $this->qrcode = $qrcode;
        return $this->save();
    }

    /**
     * Get logs related to this order
     */
    public function logs()
    {
        return $this->hasMany(JoytelLog::class, 'order_tid', 'order_tid');
    }
}
