<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Services\CashFlowProjectionService;
use App\Helpers\BusinessUnitHelper;
use Carbon\Carbon;

class Analytics extends Controller
{
  public function index()
  {
    try {
      $projectionService = app(CashFlowProjectionService::class);

      $startDate = now()->startOfMonth();
      $endDate = now()->addMonths(6)->endOfMonth();

      $projections = $projectionService
        ->setStartingBalance(0)
        ->generateProjections($startDate, $endDate, 'monthly');

      $chartData = $this->prepareCashFlowChartData($projections);
      $currentBusinessUnit = BusinessUnitHelper::getCurrentBusinessUnit();

      return view('content.dashboard.dashboards-analytics', compact('chartData', 'currentBusinessUnit'));
    } catch (\Exception $e) {
      $chartData = ['categories' => [], 'income' => [], 'expenses' => []];
      $currentBusinessUnit = BusinessUnitHelper::getCurrentBusinessUnit();
      return view('content.dashboard.dashboards-analytics', compact('chartData', 'currentBusinessUnit'));
    }
  }

  private function prepareCashFlowChartData($projections)
  {
    $categories = [];
    $income = [];
    $expenses = [];

    foreach ($projections as $projection) {
      $categories[] = $projection['projection_date']->format('M Y');
      $income[] = (float) $projection['projected_income'];
      $expenses[] = (float) $projection['projected_expenses'];
    }

    return [
      'categories' => $categories,
      'income' => $income,
      'expenses' => $expenses
    ];
  }
}
