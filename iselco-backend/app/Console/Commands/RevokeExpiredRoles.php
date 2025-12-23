<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Revoke Expired Roles Command
 * 
 * Automatically revokes expired temporary role assignments and department assignments
 * Should be run daily via Laravel scheduler
 */
class RevokeExpiredRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:revoke-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke expired temporary role and department assignments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting expired role revocation...');

        // Revoke expired temporary roles
        $expiredRoles = DB::table('role_user_temporary')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredRoles->count() > 0) {
            foreach ($expiredRoles as $assignment) {
                Log::info("Revoking expired temporary role", [
                    'user_id' => $assignment->user_id,
                    'role_id' => $assignment->role_id,
                    'expired_at' => $assignment->expires_at,
                    'reason' => $assignment->reason
                ]);
            }

            $deletedRoles = DB::table('role_user_temporary')
                ->where('expires_at', '<=', now())
                ->delete();

            $this->info("✅ Revoked {$deletedRoles} expired temporary role assignment(s)");
        } else {
            $this->info('No expired temporary roles found');
        }

        // Revoke expired department assignments
        $expiredDepartments = DB::table('department_user')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredDepartments->count() > 0) {
            foreach ($expiredDepartments as $assignment) {
                Log::info("Revoking expired department assignment", [
                    'user_id' => $assignment->user_id,
                    'department_id' => $assignment->department_id,
                    'expired_at' => $assignment->expires_at,
                    'reason' => $assignment->reason
                ]);
            }

            $deletedDepartments = DB::table('department_user')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->delete();

            $this->info("✅ Revoked {$deletedDepartments} expired department assignment(s)");
        } else {
            $this->info('No expired department assignments found');
        }

        $this->info('Expired role revocation completed!');

        return 0;
    }
}
