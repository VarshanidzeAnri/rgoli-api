<?php

namespace App\Actions\SalaryRequest;

use App\Models\SalaryRequest;
use App\Repositories\Contracts\SalaryRequestRepositoryInterface;

class DeleteSalaryRequestAction
{
    public function __construct(private SalaryRequestRepositoryInterface $requests) {}

    public function handle(SalaryRequest $request): void
    {
        $this->requests->delete($request);
    }
}
