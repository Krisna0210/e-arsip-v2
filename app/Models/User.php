<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role_id',
        'unit_id',
        'position_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cek apakah user adalah superadmin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->name === 'Superadmin';
    }

    /**
     * Cek apakah user adalah staf Tapersip (Tata Persuratan & Kearsipan)
     */
    public function isTapersip(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasModuleAccess('archive_queue')
            || $this->hasModuleAccess('loan_circulation')
            || $this->hasDocumentPermission('archive')
            || (isset($this->role) && str_contains(strtolower($this->role->name), 'tapersip'));
    }

    /**
     * Cek apakah user berhak memantau seluruh aktivitas sistem (Superadmin & Tapersip)
     */
    public function canViewAllActivities(): bool
    {
        return $this->isSuperAdmin() || $this->isTapersip();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user has access to a specific module
     */
    public function hasModuleAccess(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->role;
        if (!$role || empty($role->module_visibility)) {
            return false;
        }

        if (in_array($module, $role->module_visibility)) {
            return true;
        }

        // Backward compatibility fallback untuk role lama yang masih memiliki input_in_out
        if (in_array($module, ['input_incoming', 'input_outgoing']) && in_array('input_in_out', $role->module_visibility)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has a specific permission for a document classification/sifat
     */
    public function hasDocumentPermission(string $permissionCode, string $sifat = 'biasa'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->role;
        if (!$role) {
            return false;
        }

        return $role->permissions()
            ->where('code', $permissionCode)
            ->wherePivot('sifat', $sifat)
            ->exists();
    }

    /**
     * Get all unit IDs that this user leads (including child units without their own leaders)
     */
    public function getLedUnitIds(): array
    {
        if (!$this->position || $this->position->level > 2) {
            return [];
        }

        $ledUnits = [$this->unit_id];
        
        $allUnits = \App\Models\Unit::with(['users.position'])->get();
        
        $hasLeader = function($unit) {
            return $unit->users->contains(function($u) {
                return $u->position && $u->position->level <= 2;
            });
        };

        $findLedChildren = function($parentId) use (&$findLedChildren, $allUnits, $hasLeader, &$ledUnits) {
            $children = $allUnits->where('parent_id', $parentId);
            foreach ($children as $child) {
                if (!$hasLeader($child)) {
                    $ledUnits[] = $child->id;
                    $findLedChildren($child->id);
                }
            }
        };

        $findLedChildren($this->unit_id);

        return $ledUnits;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the division unit associated with this user's work unit.
     */
    public function getDivision(): ?Unit
    {
        return $this->unit ? $this->unit->getDivision() : null;
    }

    /**
     * Get the division code of this user's work unit.
     */
    public function getDivisionCode(): string
    {
        return $this->unit ? $this->unit->getDivisionCode() : '';
    }
}
