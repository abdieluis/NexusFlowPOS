<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $todaySales = Sale::whereDate('created_at', today())
            ->sum('total');

        $transactions = Sale::whereDate('created_at', today())
            ->count();

        $customers = Customer::count();

        $lowStock = Product::whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        $recentSales = Sale::latest()
            ->take(5)
            ->get();

        $lowStockProducts = Product::whereColumn('stock', '<=', 'minimum_stock')
            ->take(5)
            ->get();

        $weeklySales = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->take(7)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'todaySales' => $todaySales,
                'transactions' => $transactions,
                'customers' => $customers,
                'lowStock' => $lowStock,
            ],

            'recentSales' => $recentSales,

            'lowStockProducts' => $lowStockProducts,

            'weeklySales' => $weeklySales,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
