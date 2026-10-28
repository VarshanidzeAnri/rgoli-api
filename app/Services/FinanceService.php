<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\ClientPaymentRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\SalaryRequestRepositoryInterface;
use App\Support\Money;

/**
 * Finance page KPIs and per-project budget breakdown. Money stays as decimal strings (bcmath).
 */
class FinanceService
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
        private SalaryRequestRepositoryInterface $salaries,
        private ClientPaymentRepositoryInterface $payments,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $projects = $this->projects->allVisibleWithTotals($user);

        $total = Money::of(0);
        $paid = Money::of(0);
        $rows = [];
        foreach ($projects as $project) {
            $agreed = $project->agreedPrice();
            $projectPaid = $project->paidAmount();
            $total = Money::add($total, $agreed);
            $paid = Money::add($paid, $projectPaid);
            $rows[] = [
                'project' => ['id' => $project->id, 'key' => $project->key, 'name' => $project->name, 'status' => $project->status->value],
                'budget' => $project->budget,
                'discount_price' => $project->discount_price,
                'agreed_price' => $agreed,
                'paid' => $projectPaid,
                'remaining' => Money::sub($agreed, $projectPaid),
            ];
        }

        $salary = $this->salaries->totals($user);

        return [
            'total_budget' => $total,
            'paid_by_clients' => $paid,
            'paid_percent' => Money::percent($paid, $total),
            'remaining' => Money::sub($total, $paid),
            'approved_salaries' => $salary['approved_amount'],
            'pending_requests' => $salary['pending_count'],
            'pending_amount' => $salary['pending_amount'],
            'payments_this_month' => $this->payments->totalVisible($user, now()->startOfMonth()->toDateString(), now()->toDateString()),
            'projects' => $rows,
        ];
    }
}
