<?php

namespace App\Actions\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Wipes the database and re-seeds demo data. Refuses to run unless APP_DEMO=true.
 */
class ResetDemoDataAction
{
    public function handle(int $actorId): void
    {
        if (! (bool) config('rgoli.demo')) {
            throw new RuntimeException('Demo reset is disabled.');
        }

        Log::channel('auth')->warning('system.reset_demo', ['by' => $actorId]);
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    }
}
