<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $sales = Sale::query()
            ->when($request->user()->role === 'demo', fn ($query) => $query->where('user_id', $request->user()->id))
            ->when(isset($filters['start_date']), fn ($query) => $query->whereDate('sold_at', '>=', $filters['start_date']))
            ->when(isset($filters['end_date']), fn ($query) => $query->whereDate('sold_at', '<=', $filters['end_date']));

        $dailySales = (clone $sales)->selectRaw('DATE(sold_at) as sale_date, COUNT(*) as transaction_count, SUM(total) as revenue')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();
        $saleIds = (clone $sales)->select('id');
        $items = SaleItem::whereIn('sale_id', $saleIds);
        $transactions = (clone $sales)->with('user')->latest('sold_at')->paginate(15)->withQueryString();

        return view('reports.index', [
            'transactions' => $transactions,
            'dailySales' => $dailySales,
            'transactionCount' => (clone $sales)->count(),
            'revenue' => (clone $sales)->sum('total'),
            'itemsSold' => (clone $items)->sum('quantity'),
            'estimatedProfit' => (clone $items)->sum(DB::raw('(unit_price - purchase_price) * quantity')),
            'topProducts' => (clone $items)->select('product_id', 'product_name', DB::raw('SUM(quantity) as quantity_sold'), DB::raw('SUM(subtotal) as revenue'))
                ->groupBy('product_id', 'product_name')
                ->orderByDesc('quantity_sold')
                ->limit(10)
                ->get(),
        ]);
    }
}
