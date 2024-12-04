<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class PublishGuardRoaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:guard-roaster';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Guard Roaster';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userRole = Role::where('name', 'Security Guard')->first();

        $query = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        });
        $securityGuards = $query->latest()->get();
       
        foreach ($securityGuards as $user) {
        //    echo"<pre>"; print_R($user); die();
        
        }
    }
}
