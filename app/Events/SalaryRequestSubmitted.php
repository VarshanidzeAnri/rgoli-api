<?php

namespace App\Events;

use App\Models\SalaryRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalaryRequestSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public SalaryRequest $request) {}
}
