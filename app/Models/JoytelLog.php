<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JoytelLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'transaction_id',
        'system_type',
        'endpoint',
        'method',
        'request_headers',
        'request_body',
        'response_headers',
        'response_body',
        'response_status',
        'response_code',
        'response_time',
        'signature',
        'order_tid',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'response_status' => 'integer',
        'response_time' => 'decimal:2',
    ];

    /**
     * System type constants
     */
    const SYSTEM_WAREHOUSE = 'warehouse';
    const SYSTEM_RSP = 'rsp';

    /**
     * Scope for warehouse system logs
     */
    public function scopeWarehouse($query)
    {
        return $query->where('system_type', self::SYSTEM_WAREHOUSE);
    }

    /**
     * Scope for RSP system logs
     */
    public function scopeRsp($query)
    {
        return $query->where('system_type', self::SYSTEM_RSP);
    }

    /**
     * Scope for filtering by endpoint
     */
    public function scopeEndpoint($query, $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Scope for filtering by order TID
     */
    public function scopeForOrder($query, $orderTid)
    {
        return $query->where('order_tid', $orderTid);
    }

    /**
     * Check if request was successful
     */
    public function isSuccessful(): bool
    {
        return $this->response_code === '000' || 
               ($this->response_status >= 200 && $this->response_status < 300);
    }

    /**
     * Get related order
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_tid', 'order_tid');
    }
}
