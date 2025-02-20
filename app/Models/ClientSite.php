<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;

class ClientSite extends Model
{
    use HasFactory;
    protected $guarded =[];
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function clientAccount()
    {
        return $this->hasMany(ClientAccount::class, 'client_site_id');
    }

    public function clientOperation()
    {
        return $this->hasMany(ClientOperation::class, 'client_site_id');
    }
}
