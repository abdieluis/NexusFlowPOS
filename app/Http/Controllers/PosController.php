<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Sale;
use App\Models\Inventory;
use App\Models\CashRegister;
use App\Models\CustomerCreditMovement;
use App\Models\PaymentMethods;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductUnit;
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

        $lowStock = Inventory::join('products','inventories.product_id','=','products.id')
            ->whereColumn('inventories.stock', '<=', 'products.stock_alert')
            ->count();

        // $products = Product::with(['category'])->get();

        $products = Product::with([
            'category',
            'inventories' => function ($query) use ($user) {
                $query->where('branch_id', $user->branch_id);
            },
            'productUnits.saleType',
            'defaultUnit',
            'packageOpenings',
            'tax'
        ])->get();

        $categories = Category::all();

        $activeRegister = CashRegister::where('branch_id', $user->branch_id)
            ->where('status', 'open')
            ->first();

        $paymentMethods = PaymentMethods::where('business_id', $user->business_id)
            ->select('id', 'name')
            ->get();

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
            'paymentMethods' => $paymentMethods,
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
            'items' => 'required|array|min:1',
            'items.*.product_id'=>'required|exists:products,id',
            'items.*.quantity'=>'required|numeric|min:0.001',
            'items.*.price'=>'required|numeric',
            'subtotal' => 'required|numeric',
            'tax' => 'nullable|numeric',
            'total' => 'required|numeric',
            'paid' => 'required|numeric',
            'customer_id' => 'required|exists:customers,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            // ============================
            // CAJA ABIERTA
            // ============================
            $activeCashRegister = CashRegister::where('branch_id',$user->branch_id)
                ->where('status','open')
                ->latest()
                ->first();
            if(!$activeCashRegister){
                throw new \Exception(
                    'No hay una caja abierta.'
                );
            }

            $customer = Customer::findOrFail(
                $request->customer_id
            );
            // ==========================
            // CRÉDITO
            // ============================
            $pendingAmount = max(
                0,
                $request->total - $request->paid
            );

            if(
                $customer->id == 1 &&
                $pendingAmount > 0
            ){
                throw new \Exception(
                    'Público General debe liquidar la venta.'
                );
            }

            if(
                $customer->id != 1 &&
                $pendingAmount > 0
            ){
                $available =
                    $customer->credit_limit -
                    $customer->current_balance;
                if($pendingAmount > $available){
                    throw new \Exception(
                        'Crédito insuficiente.'
                    );
                }
            }

            // ============================
            // ESTADO PAGO
            // ============================
            if($pendingAmount == 0){
                $paymentStatus='paid';
            }elseif($request->paid == 0){
                $paymentStatus='pending';
            }else{
                $paymentStatus='partial';
            }

            // ============================
            // CREAR VENTA
            // ============================
            $sale = Sale::create([
                'business_id'=>$user->business_id,
                'branch_id'=>$user->branch_id,
                'cash_register_id'=>$activeCashRegister->id,
                'user_id'=>$user->id,
                'customer_id'=>$customer->id,
                'subtotal'=>$request->subtotal,
                'discount'=>$request->discount ?? 0,
                'tax'=>$request->tax ?? 0,
                'total'=>$request->total,
                'paid'=>$request->paid,
                'change_amount'=>
                    max(
                        0,
                        $request->paid - $request->total
                    ),
                'payment_method_id'=>
                    $request->payment_method_id,
                'payment_status'=>$paymentStatus,
                'status'=>'completed',
                'sold_at'=>now(),
            ]);

            // ============================
            // CREDITO CLIENTE
            // ============================
            if(
                $pendingAmount > 0 &&
                $customer->id != 1
            ){
                $oldBalance =
                    $customer->current_balance;
                $newBalance =
                    $oldBalance + $pendingAmount;
                $customer->update([
                    'current_balance'=>$newBalance
                ]);
                CustomerCreditMovement::create([
                    'business_id'=>$user->business_id,
                    'customer_id'=>$customer->id,
                    'user_id'=>$user->id,
                    'sale_id'=>$sale->id,
                    'payment_method_id'=>
                        $request->payment_method_id,
                    'type'=>'charge',
                    'amount'=>$pendingAmount,
                    'previous_balance'=>$oldBalance,
                    'new_balance'=>$newBalance,
                    'notes'=>'Venta crédito #'.$sale->id,
                ]);
            }

            // ============================
            // ITEMS
            // ============================
            foreach($request->items as $item){
                /*
                |--------------------------------------------------------------------------
                | Buscar inventario
                |--------------------------------------------------------------------------
                */
                $inventory = Inventory::where('branch_id',$user->branch_id)
                    ->where('product_id',$item['product_id'])
                    ->where(
                        'product_variant_id',
                        $item['product_variant_id'] ?? null
                    )
                    ->first();
                if(!$inventory){
                    throw new \Exception(
                        "No existe inventario para {$item['name']}"
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Calcular cantidad real a descontar
                |--------------------------------------------------------------------------
                */
                $quantityToSubtract = $item['quantity'];
                /*
                |--------------------------------------------------------------------------
                | Si tiene unidad de venta buscamos conversión
                |--------------------------------------------------------------------------
                */
                $productUnit = null;
                if(!empty($item['product_unit_id'])){
                    $productUnit = ProductUnit::find(
                        $item['product_unit_id']
                    );
                    if($productUnit){
                        $quantityToSubtract =
                            $item['quantity'] *
                            $productUnit->conversion_factor;
                    }
                }
                /*
                |--------------------------------------------------------------------------
                | Validar stock real
                |--------------------------------------------------------------------------
                */
                if(
                    $inventory->stock <
                    $quantityToSubtract
                ){
                    throw new \Exception(
                        "Stock insuficiente para {$item['name']}"
                    );
                }

                $sale->items()->create([
                    'product_id'=>$item['product_id'],
                    'product_variant_id'=>$item['product_variant_id'] ?? null,
                    'product_unit_id'=>$item['product_unit_id'] ?? null,
                    'sale_type_id'=>$item['sale_type_id'] ??
                        ($productUnit->sale_type_id ?? null),
                    'quantity'=>$item['quantity'],
                    'price'=>$item['price'],
                    'subtotal'=>$item['subtotal'] ??
                        ($item['quantity'] * $item['price']),
                ]);

                $stockBefore =
                    $inventory->stock;
                // descontar inventario
                $inventory->decrement(
                    'stock',
                    $quantityToSubtract
                );
                // movimiento inventario
                InventoryMovement::create([
                    'branch_id'=>$user->branch_id,
                    'product_id'=>$item['product_id'],
                    'product_variant_id'=>
                        $item['product_variant_id'] ?? null,
                    'type'=>'sale',
                    'quantity'=>$quantityToSubtract,
                    'reference_type'=>'sale',
                    'reference_id'=>$sale->id,
                    'notes'=>
                        'Venta #'.$sale->id.
                        ' Stock anterior: '.$stockBefore.
                        ' Stock actual: '.$inventory->stock,
                    'created_by'=>$user->id,
                ]);
            }
            DB::commit();
            return response()->json([
                'message'=>'Venta creada correctamente',
                'sale'=>$sale
            ]);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'message'=>$e->getMessage()
            ],500);
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
