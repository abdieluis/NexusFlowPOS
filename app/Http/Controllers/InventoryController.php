<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inventories = Inventory::with([
            'product',
            'variant',
            'branch'
        ])->get();

        return Inertia::render('Inventory/Index', [
            'inventories' => $inventories
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

    public function adjustStock(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'type'         => 'required|in:in,out', // 'in' = entrada, 'out' = salida
            'quantity'     => 'required|numeric|gt:0',
            'reason'       => 'required|string|max:255', // Compra, Venta, Merma, Ajuste, etc.
        ]);

        DB::transaction(function () use ($request) {
            $inventory = Inventory::findOrFail($request->inventory_id);

            // 1. Calcular el nuevo stock
            $previousStock = $inventory->stock;
            $quantity = $request->quantity;

            if ($request->type === 'in') {
                $newStock = $previousStock + $quantity;
            } else {
                if ($previousStock < $quantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantity' => 'El stock actual es insuficiente para realizar esta salida.'
                    ]);
                }
                $newStock = $previousStock - $quantity;
            }

            // 2. Actualizar el stock en la tabla `inventories`
            $inventory->update([
                'stock' => $newStock
            ]);

            // 3. Registrar el movimiento en `inventory_movements`
            InventoryMovement::create([
                'inventory_id'  => $inventory->id,
                'branch_id'     => $inventory->branch_id,
                'product_id'    => $inventory->product_id,
                'variant_id'    => $inventory->variant_id,
                'user_id'       => auth()->id(), // Usuario que hace el movimiento
                'type'          => $request->type,
                'quantity'      => $quantity,
                'previous_stock'=> $previousStock,
                'new_stock'     => $newStock,
                'reason'        => $request->reason,
            ]);
        });

        return redirect()->back()->with('success', 'Movimiento de inventario registrado correctamente.');
    }
}
