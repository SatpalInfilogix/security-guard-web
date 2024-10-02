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

class User extends Authenticatable
{
    use HasRoles,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'profile_picture',
        'date_of_birth',
        'password',
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

}
