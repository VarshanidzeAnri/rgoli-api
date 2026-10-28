<?php

namespace App\Actions\Phase;

use App\Models\ProjectPhase;
use App\Repositories\Contracts\PhaseRepositoryInterface;

class DeletePhaseAction
{
    public function __construct(private PhaseRepositoryInterface $phases) {}

    public function handle(ProjectPhase $phase): void
    {
        $this->phases->delete($phase);
    }
}
