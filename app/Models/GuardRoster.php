<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Client;
use App\Models\ClientSite;

class GuardRoster extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'guard_id'); // 'guard_id' is the foreign key
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id'); // 'client_id' is the foreign key
    }

    public function clientSite()
    {
        return $this->belongsTo(ClientSite::class, 'client_site_id'); // 'client_id' is the foreign key
    }
}
