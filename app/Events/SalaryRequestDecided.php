<?php

namespace App\Events;

use App\Models\SalaryRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalaryRequestDecided
{
    use Dispatchable, SerializesModels;

    public function __construct(public SalaryRequest $request, public User $decider) {}
}
