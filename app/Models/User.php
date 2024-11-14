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

class User extends Authenticatable
{
    use HasRoles,HasFactory, Notifiable,HasApiTokens;

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
        'is_saturatory'
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

    public function userDocuments()
    {
        return $this->hasone(UsersDocuments::class);
    }

}
