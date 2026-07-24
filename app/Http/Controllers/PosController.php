<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Sale;
use App\Models\Inventory;
use App\Models\CashRegister;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\InventoryMovement;

class PosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $todaySales = Sale::whereDate('created_at', today())->sum('total');
        $transactions = Sale::whereDate('created_at', today())->count();

        $customerCount = Customer::count();

        $customers = Customer::all();

        $lowStock = Inventory::join(
            'products',
            'inventories.product_id',
            '=',
            'products.id'
        )
        ->whereColumn('inventories.stock', '<=', 'products.stock_alert')
        ->count();

        $products = Product::with(['category'])->get();

        $categories = Category::all();

        $activeRegister = CashRegister::where('branch_id', $user->branch_id)
            ->where('status', 'open')
            ->first();

        return Inertia::render('Pos/Index', [
            'stats' => [
                'todaySales'   => $todaySales,
                'transactions' => $transactions,
                'customers'    => $customerCount,
                'lowStock'     => $lowStock,
            ],

            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'activeRegister' => $activeRegister,
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
        $request->validate([
            'items'          => 'required|array|min:1',
            'subtotal'       => 'required|numeric',
            'tax'            => 'nullable|numeric',
            'total'          => 'required|numeric',
            'paid'           => 'required|numeric',
            'customer_id'    => 'required|exists:customers,id',
            'payment_method' => 'required|string|in:cash,card,transfer', // <--- Nueva validación
        ]);

        $customer = Customer::findOrFail($request->customer_id);

        DB::beginTransaction();

        try {
            $user = Auth::user();

            // 1. VERIFICAR QUE HAYA UNA CAJA ABIERTA PARA ESTA SUCURSAL / USUARIO
            // $activeCashRegister = CashRegister::where('branch_id', $user->branch_id)
            //     ->where('status', 'open')
            //     ->where(function ($query) use ($user) {
            //         $query->where('opened_by', $user->id)
            //               ->orWhereNull('opened_by');
            //     })
            //     ->latest()
            //     ->first();

            // if (!$activeCashRegister) {
            //     throw new \Exception('No hay ninguna caja abierta en esta sucursal. Abre una caja antes de realizar cobros.');
            // }
            $activeCashRegister = CashRegister::where('branch_id', $user->branch_id)
                ->where('status', 'open')
                ->latest()
                ->first();

            // dd($activeCashRegister);

            if (!$activeCashRegister) {
                throw new \Exception('No hay ninguna caja abierta en esta sucursal. Abre una caja antes de realizar cobros.');
            }

            // Calcular saldo pendiente
            $pendingAmount = max(0, $request->total - $request->paid);

            // Cliente público general no maneja crédito
            if ($customer->id == 1 && $pendingAmount > 0) {
                throw new \Exception('El cliente Público General debe liquidar el total de la venta.');
            }

            // Clientes con crédito
            if ($customer->id != 1 && $pendingAmount > 0) {
                $availableCredit = $customer->credit_limit - $customer->current_balance;
                if ($pendingAmount > $availableCredit) {
                    throw new \Exception(
                        'El cliente no tiene crédito disponible. Crédito disponible: $' .
                        number_format($availableCredit, 2)
                    );
                }
                // Aumentar saldo pendiente del cliente
                $customer->increment('current_balance', $pendingAmount);
            }

            if ($pendingAmount == 0) {
                $paymentStatus = 'paid';
            } elseif ($request->paid == 0) {
                $paymentStatus = 'pending';
            } else {
                $paymentStatus = 'partial';
            }

            // 2. CREAR LA VENTA CON LA RELACIÓN DE LA CAJA Y EL MÉTODO DE PAGO
            $sale = Sale::create([
                'business_id'      => $user->business_id,
                'branch_id'        => $user->branch_id,
                'cash_register_id' => $activeCashRegister->id,
                'user_id'          => $user->id,
                'customer_id'      => $request->customer_id,
                'subtotal'         => $request->subtotal,
                'discount'         => $request->discount ?? 0,
                'tax'              => $request->tax ?? 0,
                'total'            => $request->total,
                'paid'             => $request->paid,
                'change_amount'    => max(0, $request->paid - $request->total),
                'payment_method'   => $request->payment_method, // <--- Guardamos cash, card, o transfer
                'payment_status'   => $paymentStatus,
                'status'           => 'completed',
                'sold_at'          => now(),
            ]);

            foreach ($request->items as $item) {
                $inventory = Inventory::where('product_id', $item['id'])
                    ->where('branch_id', $user->branch_id)
                    ->first();

                if (!$inventory) {
                    throw new \Exception("El producto {$item['name']} no tiene inventario.");
                }

                if ($inventory->stock < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para {$item['name']}");
                }

                // Crear detalle de venta
                $sale->items()->create([
                    'product_id' => $item['id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);

                // Stock anterior
                $stockBefore = $inventory->stock;

                // Descontar inventario
                $inventory->decrement('stock', $item['quantity']);

                // Movimiento de inventario
                InventoryMovement::create([
                    'branch_id'      => $user->branch_id,
                    'product_id'     => $item['id'],
                    'variant_id'     => null,
                    'type'           => 'sale',
                    'quantity'       => $item['quantity'],
                    'reference_type' => 'sale',
                    'reference_id'   => $sale->id,
                    'notes'          => 'Venta #' . $sale->id .
                                        ' | Stock anterior: ' . $stockBefore .
                                        ' | Stock actual: ' . $inventory->stock,
                    'created_by'     => $user->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Venta creada correctamente',
                'sale'    => $sale
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
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
