<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\GuardAdditionalInformation;
use App\Models\ContactDetail;
use App\Models\UsersBankDetail;
use App\Models\UsersKinDetail;
use App\Models\UsersDocuments;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasRoles, HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'surname',
        'last_name',
        'email',
        'phone_number',
        'profile_picture',
        'date_of_birth',
        'password',
        'status',
        'user_code',
        'is_statutory',
        'current_time_zone',
        'previous_role_id',
        'current_role_id',
        'promotion_date',
        'promotion_remarks',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
     * Automatically generate a user code when the guard is activated for the first time.
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($guard) {
            if ($guard->status == 'Active' && is_null($guard->user_code)) {
                $lastGuard = self::where('user_code', 'LIKE', 'G%')->orderBy('user_code', 'desc')->first();

                if ($lastGuard) {
                    $lastCodeNumber = (int) substr($lastGuard->user_code, 1);
                    $newCodeNumber = $lastCodeNumber + 1;
                    $guard->user_code = 'G' . str_pad($newCodeNumber, 6, '0', STR_PAD_LEFT);
                } else {
                    $guard->user_code = 'G' . str_pad(1, 6, '0', STR_PAD_LEFT);
                }
            }
        });
    }

    public function guardAdditionalInformation()
    {
        return $this->hasOne(GuardAdditionalInformation::class);
    }

    public function contactDetail()
    {
        return $this->hasOne(ContactDetail::class);
    }

    public function usersBankDetail()
    {
        return $this->hasOne(UsersBankDetail::class);
    }

    public function usersKinDetail()
    {
        return $this->hasOne(UsersKinDetail::class);
    }

    public function userDocuments()
    {
        return $this->hasone(UsersDocuments::class);
    }

    public function guardRoasters()
    {
        return $this->hasMany(GuardRoster::class, 'guard_id');
    }

    public function employeeDeductionDetails()
    {
        return $this->hasMany(EmployeeDeduction::class, 'employee_id');
    }

    public function previousRole()
    {
        return $this->belongsTo(Role::class, 'previous_role_id');
    }

    public function currentRole()
    {
        return $this->belongsTo(Role::class, 'current_role_id');
    }

    public function getPromotionDateAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }
}
