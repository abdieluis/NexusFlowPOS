<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $todaySales = Sale::whereDate('created_at', today())->sum('total');

        $transactions = Sale::whereDate('created_at', today())->count();

        $customers = Customer::count();

        $lowStock = Inventory::join(
            'products',
            'inventories.product_id',
            '=',
            'products.id'
        )
        ->whereColumn(
            'inventories.stock',
            '<=',
            'products.stock_alert'
        )
        ->count();

        return Inertia::render('Pos/Dashboard', [
            'stats' => [
                'todaySales'   => $todaySales,
                'transactions' => $transactions,
                'customers'    => $customers,
                'lowStock'     => $lowStock,
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Pos/Inventory');
    }

    public function analytics()
    {
        return Inertia::render('Pos/Analytics');
    }

    public function catalog()
    {
        return Inertia::render('Pos/Catalog');
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
