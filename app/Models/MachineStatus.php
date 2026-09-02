<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Events\MachineStatusChanged;

class MachineStatus extends Model
{
    use HasFactory;

    protected $table = 'machine_status';

    protected $fillable = [
        'machine_ip',
        'machine_name',
        'status',
        'last_ping',
        'response_time',
        'total_users',
        'total_logs',
        'is_default',
    ];

    protected $casts = [
        'last_ping' => 'datetime',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function ($machine) {
            Cache::put("machine_{$machine->id}_status", $machine->status, now()->addMinutes(5));
        });
    }

    /**
     * Cek apakah mesin online
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Cek apakah mesin offline
     */
    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    /**
     * Cek apakah mesin default
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Update status mesin
     */
    public function updateStatus(bool $isOnline, ?int $responseTime = null)
    {
        $oldStatus = $this->status;
        $newStatus = $isOnline ? 'online' : 'offline';
        
        $this->update([
            'status' => $newStatus,
            'last_ping' => now(),
            'response_time' => $responseTime,
        ]);

        // Update cache
        Cache::put("machine_{$this->id}_status", $newStatus, now()->addMinutes(5));

        // Broadcast event jika status berubah
        if ($oldStatus !== $newStatus) {
            event(new MachineStatusChanged($this));
        }
    }

    /**
     * Update statistik mesin
     */
    public function updateStats(int $totalUsers, int $totalLogs)
    {
        $this->update([
            'total_users' => $totalUsers,
            'total_logs' => $totalLogs,
        ]);
    }

    /**
     * Scope untuk mesin default
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get status dengan label
     */
    public function getStatusLabel(): string
    {
        return $this->status === 'online' ? 'Online' : 'Offline';
    }

    /**
     * Get badge class berdasarkan status
     */
    public function getStatusBadgeClass(): string
    {
        return $this->status === 'online' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
    }

    /**
     * Get time since last ping dalam format readable
     */
    public function getLastPingHuman(): string
    {
        if (!$this->last_ping) {
            return 'Belum pernah';
        }
        
        return $this->last_ping->diffForHumans();
    }

    /**
     * Cek apakah mesin perlu di-check (timeout 5 menit)
     */
    public function needsCheck(): bool
    {
        if (!$this->last_ping) {
            return true;
        }
        
        return $this->last_ping->diffInMinutes(now()) >= 5;
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTime(): string
    {
        return $this->response_time ? "{$this->response_time}ms" : '-';
    }
}
