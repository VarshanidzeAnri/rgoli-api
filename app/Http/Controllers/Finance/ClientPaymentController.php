<?php

namespace App\Http\Controllers\Finance;

use App\Actions\ClientPayment\DeleteClientPaymentAction;
use App\Actions\ClientPayment\SaveClientPaymentAction;
use App\DTOs\Filters\FinanceFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ClientPaymentRequest;
use App\Http\Requests\Finance\IndexFinanceRequest;
use App\Http\Resources\ClientPaymentResource;
use App\Models\ClientPayment;
use App\Models\Project;
use App\Repositories\Contracts\ClientPaymentRepositoryInterface;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Finance
 */
class ClientPaymentController extends Controller
{
    public function __construct(private ClientPaymentRepositoryInterface $payments) {}

    /**
     * Finance KPIs and per-project budgets (finance.view).
     */
    public function summary(Request $request, FinanceService $finance): JsonResponse
    {
        $this->authorize('viewAny', ClientPayment::class);

        return response()->json(['data' => $finance->summary($this->actor($request))]);
    }

    /**
     * All client payments on visible projects (finance.view).
     */
    public function index(IndexFinanceRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ClientPayment::class);

        return ClientPaymentResource::collection($this->payments->paginateVisible($this->actor($request), $request->toFilters()));
    }

    /**
     * Payments of one project.
     */
    public function forProject(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewFinance', $project);

        return ClientPaymentResource::collection($this->payments->paginateVisible(
            $this->actor($request),
            new FinanceFilters(projectId: $project->id, perPage: $this->perPage($request, 50)),
        ));
    }

    /**
     * Record a payment (client_payments.manage).
     */
    public function store(ClientPaymentRequest $request, Project $project, SaveClientPaymentAction $action): JsonResponse
    {
        $this->authorize('managePayments', $project);
        /** @var array{amount: string, paid_at: string, method: string, note?: string|null} $data */
        $data = $request->validated();

        return (new ClientPaymentResource($action->handle($this->actor($request), $data, project: $project)))
            ->additional(['meta' => ['message' => __('messages.saved')]])
            ->response()->setStatusCode(201);
    }

    /**
     * Edit a payment.
     */
    public function update(ClientPaymentRequest $request, ClientPayment $payment, SaveClientPaymentAction $action): ClientPaymentResource
    {
        $this->authorize('update', $payment);
        /** @var array{amount?: string, paid_at?: string, method?: string, note?: string|null} $data */
        $data = $request->validated();

        return (new ClientPaymentResource($action->handle($this->actor($request), $data, payment: $payment)))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Delete a payment.
     */
    public function destroy(Request $request, ClientPayment $payment, DeleteClientPaymentAction $action): JsonResponse
    {
        $this->authorize('delete', $payment);
        $action->handle($this->actor($request), $payment);

        return $this->message('messages.deleted');
    }
}
