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
            'type'         => 'required|in:purchase,sale,adjustment,transfer',
            'quantity'     => 'required|numeric|gt:0',
            'reason'       => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request) {

            $inventory = Inventory::findOrFail($request->inventory_id);

            // Stock actual antes del movimiento
            $previousStock = $inventory->stock;
            $quantity = $request->quantity;

            // Calcular nuevo stock según tipo de movimiento
            switch ($request->type) {

                // Movimientos que aumentan inventario
                case 'purchase':
                case 'adjustment':
                    $newStock = $previousStock + $quantity;
                    break;


                // Movimientos que reducen inventario
                case 'sale':
                    if ($previousStock < $quantity) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'quantity' => 'El stock actual es insuficiente para realizar esta salida.'
                        ]);
                    }

                    $newStock = $previousStock - $quantity;
                    break;


                // Transferencia pendiente de lógica entre sucursales
                case 'transfer':
                    $newStock = $previousStock;
                    break;


                default:
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'type' => 'Tipo de movimiento no válido.'
                    ]);
            }


            // Actualizar inventario
            $inventory->update([
                'stock' => $newStock
            ]);


            // Registrar movimiento
            InventoryMovement::create([
                'branch_id'      => $inventory->branch_id,
                'product_id'     => $inventory->product_id,
                'variant_id'     => $inventory->variant_id,
                'type'           => $request->type,
                'quantity'       => $quantity,
                'reference_type' => 'inventory',
                'reference_id'   => $inventory->id,
                'notes'          => $request->reason,
                'created_by'     => auth()->id(),
            ]);
        });


        return redirect()->back()->with('success', 'Movimiento de inventario registrado correctamente.');
    }
}
