<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\AgentApplication;
use App\Models\Department;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, MustVerifyEmailTrait;

    protected $fillable = [
        'name', 
        'email', 
        'password',
        'phone',           // ← Added
        'avatar',
        'role',            // ← Added (user/agent)
        'status',          // ← Added (active/inactive)
        'department_id',   // normalized department reference
        'position',        // ← Added
        'employee_id',     // ← Added
        'specialization',  // ← Added
        'tickets_handled', // ← Added
        'rating',          // ← Added
        'last_login_at',   // ← Added
        'last_login_ip',   // ← Added
        'agent_application_id',
        'approved_at',     // ← Added
        'approved_by',     // ← Added
        'is_active',       // Keep this if you use it
        'verification_code',
        'verification_code_expires_at',
        'is_available',
            ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verification_code_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'approved_at' => 'datetime',
        'rating' => 'float',
        'tickets_handled' => 'integer',
        'preferences' => 'array',
        'notification_settings' => 'array',
        'is_available' => 'boolean',
    ];

  



    protected static function booted()
    {
        static::deleting(function ($user) {
            if ($user->role !== 'agent') {
                return;
            }

            try {
                $query = AgentApplication::where('status', 'approved');

                if ($user->agent_application_id) {
                    $query->where('id', $user->agent_application_id);
                } else {
                    $query->where('email', $user->email);
                }

                $query->update([
                    'status' => 'rejected',
                    'admin_notes' => 'Linked agent account deleted. Application marked rejected.',
                    'reviewed_by' => Auth::id(),
                ]);
            } catch (\Exception $e) {
                // Log the error but don't prevent deletion
                \Log::error('Failed to update agent application on user deletion', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    // Relationships
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function activities()
    {
        return $this->hasMany(TicketActivity::class);
    }

    public function agentRatings()
    {
        return $this->hasMany(AgentRating::class, 'agent_id');
    }

    public function givenRatings()
    {
        return $this->hasMany(AgentRating::class, 'user_id');
    }

    public function loginActivities()
    {
        return $this->hasMany(LoginActivity::class);
    }

    public function announcementReads()
    {
        return $this->hasMany(UserAnnouncementRead::class);
    }

    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function agentApplication()
    {
        return $this->belongsTo(AgentApplication::class, 'agent_application_id');
    }

    public function messageNotifications()
    {
        return $this->hasMany(MessageNotification::class);
    }

    // Role checks (using simple role column, not Spatie)
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isAgent()
    {
        return $this->role === 'agent';
    }
    
    public function isUser()
    {
        return $this->role === 'user';
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function categories()
{
    return $this->belongsToMany(Category::class, 'agent_categories', 'agent_id', 'category_id')
        ->withTimestamps();
}

    public function getDepartmentAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }

        $department = $this->getRelationValue('department');
        if ($department) {
            return $department->name;
        }

        return $this->department()->value('name');
    }

    

    // Stats
    public function getOpenTicketsCount()
    {
        return $this->tickets()->whereIn('status', ['open', 'in_progress', 'pending'])->count();
    }

    public function getResolvedTicketsCount()
    {
        return $this->tickets()->where('status', 'resolved')->count();
    }

    // Accessor for avatar
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?background=2563EB&color=fff&name=' . urlencode($this->name);
    }
    
    // Helper to increment tickets handled
    public function incrementTicketsHandled()
    {
        $this->increment('tickets_handled');
    }
    
    // Helper to update rating
    public function updateRating($newRating)
    {
        // Calculate average rating
        $total = ($this->rating * $this->tickets_handled) + $newRating;
        $this->rating = $total / ($this->tickets_handled + 1);
        $this->save();
    }

    /**
     * Send the email verification code instead of link.
     */
    public function sendEmailVerificationNotification()
    {
        // Generate a 6-digit verification code
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store the code and set expiration time (30 minutes)
        $this->update([
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => now()->addMinutes(30),
        ]);
        
        // Send the notification with the code
        $this->notify(new \App\Notifications\VerifyEmailWithCode($verificationCode));
    }

    /**
     * Check if verification code is valid.
     */
    public function isVerificationCodeValid(string $code): bool
    {
        return $this->verification_code === $code 
            && $this->verification_code_expires_at 
            && $this->verification_code_expires_at->isFuture();
    }

    /**
     * Mark email as verified using code.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();
    }

    // Accessors
    public function getAverageRatingAttribute()
    {
        return $this->agentRatings()->avg('rating') ?? 0;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = self::normalizePhilippinesPhone($value);
    }

    public static function normalizePhilippinesPhone(?string $value): ?string
    {
        $value = trim($value ?? '');

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9+]/', '', $value);

        if (preg_match('/^09(\d{9})$/', $value, $matches)) {
            return '+63' . $matches[1];
        }

        if (preg_match('/^639(\d{9})$/', $value)) {
            return '+' . $value;
        }

        if (preg_match('/^\+639(\d{9})$/', $value)) {
            return $value;
        }

        return $value;
    }
}