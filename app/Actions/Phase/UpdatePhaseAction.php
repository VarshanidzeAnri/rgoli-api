<?php

namespace App\Actions\Phase;

use App\Models\ProjectPhase;
use App\Repositories\Contracts\PhaseRepositoryInterface;

class UpdatePhaseAction
{
    public function __construct(private PhaseRepositoryInterface $phases) {}

    /**
     * @param  array<string, mixed>  $data  API field "order" maps to column sort_order
     */
    public function handle(ProjectPhase $phase, array $data): ProjectPhase
    {
        if (array_key_exists('order', $data)) {
            $data['sort_order'] = $data['order'];
            unset($data['order']);
        }

        return $this->phases->update($phase, $data);
    }
}
