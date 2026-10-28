<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Reports
 *
 * Gated by the permission middleware on the routes (reports.view / reports.export).
 */
class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    /**
     * Completion %, average progress, outstanding budget, task status, planned vs paid, workload.
     *
     * @queryParam from string Y-m-d
     * @queryParam to string Y-m-d
     */
    public function summary(Request $request): JsonResponse
    {
        $range = $request->validate([
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date', 'after_or_equal:from'],
        ]);

        return response()->json(['data' => $this->reports->summary(
            $this->actor($request),
            isset($range['from']) ? (string) $range['from'] : null,
            isset($range['to']) ? (string) $range['to'] : null,
        )]);
    }

    /**
     * CSV export (UTF-8 with BOM).
     *
     * @queryParam type string required tasks or projects. Example: tasks
     */
    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate(['type' => ['required', 'in:tasks,projects']]);

        return $this->reports->exportCsv($this->actor($request), (string) $data['type']);
    }
}
