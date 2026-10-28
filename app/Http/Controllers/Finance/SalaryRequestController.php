<?php

namespace App\Http\Controllers\Finance;

use App\Actions\SalaryRequest\DecideSalaryRequestAction;
use App\Actions\SalaryRequest\DeleteSalaryRequestAction;
use App\Actions\SalaryRequest\SubmitSalaryRequestAction;
use App\Actions\SalaryRequest\UpdateSalaryRequestAction;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\DecideSalaryRequest;
use App\Http\Requests\Finance\IndexFinanceRequest;
use App\Http\Requests\Finance\SalaryRequestRequest;
use App\Http\Resources\SalaryRequestResource;
use App\Models\SalaryRequest;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\SalaryRequestRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Finance
 */
class SalaryRequestController extends Controller
{
    /**
     * Salary requests: all on visible projects with finance.view, otherwise only mine.
     */
    public function index(IndexFinanceRequest $request, SalaryRequestRepositoryInterface $requests): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalaryRequest::class);

        return SalaryRequestResource::collection($requests->paginateVisible($this->actor($request), $request->toFilters()));
    }

    /**
     * Submit my salary request (salary.request).
     */
    public function store(SalaryRequestRequest $request, ProjectRepositoryInterface $projects, SubmitSalaryRequestAction $action): JsonResponse
    {
        $project = $projects->findOrFail((int) $request->validated('project_id'));
        $this->authorize('requestSalary', $project);
        /** @var array{project_id: int, title: string, amount: string, note?: string|null} $data */
        $data = $request->validated();

        return (new SalaryRequestResource($action->handle($this->actor($request), $data)))
            ->additional(['meta' => ['message' => __('messages.saved')]])
            ->response()->setStatusCode(201);
    }

    /**
     * Edit my pending request.
     */
    public function update(SalaryRequestRequest $request, SalaryRequest $salaryRequest, UpdateSalaryRequestAction $action): SalaryRequestResource
    {
        $this->authorize('update', $salaryRequest);
        /** @var array{title?: string, amount?: string, note?: string|null} $data */
        $data = $request->validated();

        return (new SalaryRequestResource($action->handle($salaryRequest, $data)))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Withdraw my pending request.
     */
    public function destroy(SalaryRequest $salaryRequest, DeleteSalaryRequestAction $action): JsonResponse
    {
        $this->authorize('delete', $salaryRequest);
        $action->handle($salaryRequest);

        return $this->message('messages.deleted');
    }

    /**
     * Approve (salary.approve). Records who and when.
     */
    public function approve(DecideSalaryRequest $request, SalaryRequest $salaryRequest, DecideSalaryRequestAction $action): SalaryRequestResource
    {
        return $this->decide($request, $salaryRequest, $action, PaymentStatus::Approved);
    }

    /**
     * Reject (salary.approve).
     */
    public function reject(DecideSalaryRequest $request, SalaryRequest $salaryRequest, DecideSalaryRequestAction $action): SalaryRequestResource
    {
        return $this->decide($request, $salaryRequest, $action, PaymentStatus::Rejected);
    }

    private function decide(DecideSalaryRequest $request, SalaryRequest $salaryRequest, DecideSalaryRequestAction $action, PaymentStatus $decision): SalaryRequestResource
    {
        $this->authorize('decide', $salaryRequest);
        $note = $request->validated('note');

        return (new SalaryRequestResource($action->handle($this->actor($request), $salaryRequest, $decision, is_string($note) ? $note : null)))
            ->additional(['meta' => ['message' => __('messages.salary_'.$decision->value)]]);
    }
}
