<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $user = auth()->user();
        $sales = Sale::query()->when(
            $user->role !== 'admin',
            fn (Builder $query) => $query->where('user_id', $user->id),
        );

        return view('dashboard', [
            'productCount' => Product::count(),
            'categoryCount' => Category::count(),
            'saleCount' => (clone $sales)->count(),
            'lowStockCount' => Product::whereColumn('stock', '<=', 'minimum_stock')->count(),
            'lowStockProducts' => Product::with('category')
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->orderBy('stock')
                ->orderBy('name')
                ->limit(6)
                ->get(),
            'todayRevenue' => (clone $sales)->whereDate('sold_at', $today)->sum('total'),
            'monthRevenue' => (clone $sales)->where('sold_at', '>=', $monthStart)->sum('total'),
            'latestSales' => (clone $sales)->with('user')->latest('sold_at')->limit(8)->get(),
        ]);
    }
}
