<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'password_hash', 'first_name', 'last_name', 'role',
        'department_id', 'academic_status_declared', 'academic_status_last_declared_at',
        'academic_status_declaration_count', 'is_honor_system_acknowledged',
        'is_banned', 'banned_reason', 'banned_at', 'banned_by',
        'supplementary_details', 'mobile', 'email', 'ticket_lane',
        'must_change_password', 'temporary_password_expires_at', 'last_login_at'
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * SEC-04 whitelist (TODO-013): the ONLY columns that may be serialized when
     * a User row is embedded in another resource's response (message senders,
     * spec professors...). mobile/email/supplementary_details are self-only PII
     * and must never leave the server attached to somebody else's view.
     */
    public const PUBLIC_COLS = 'id,first_name,last_name,role';

    protected $casts = [
        'must_change_password' => 'boolean',
        'is_banned' => 'boolean',
        'is_honor_system_acknowledged' => 'boolean',
        'academic_status_last_declared_at' => 'datetime',
        'temporary_password_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'banned_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function passwordHistories()
    {
        return $this->hasMany(PasswordHistory::class, 'user_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class, 'user_id');
    }

    public function honorFlags()
    {
        return $this->hasMany(HonorFlag::class, 'student_id');
    }

    public function resources()
    {
        return $this->hasMany(Resource::class, 'uploader_id');
    }

    // Honor System helpers
    public function hasDeclaredHonor(): bool
    {
        return !is_null($this->academic_status_declared);
    }

    public function isFinalSemester(): bool
    {
        return $this->academic_status_declared === 'final_semester';
    }
}