<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Tax;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\OpenFoodFactsService;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryMovement;
use App\Models\ProductUnit;
use App\Models\ProductPackageOpening;
use App\Services\CategoryResolverService;
use App\Models\SaleType;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with(['category','productUnits.saleType'])
            ->latest()
            ->get();


        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::orderBy('name')
            ->select('id', 'name')
            ->get();

        $taxes = Tax::orderBy('name')
            ->select('id', 'name')
            ->get();

        return Inertia::render('Products/Create', [
            'categories' => $categories,
            'taxes' => $taxes,
            'saleTypes' => SaleType::orderBy('id')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|max:255',
            'sku' => 'required|max:255|unique:products,sku',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:1000',
            'stock' => 'nullable|integer|min:0',
            'product_units' => 'nullable|array',
            'product_units.*.sale_type_id' =>
                'required|exists:sale_types,id',
            'product_units.*.price' =>
                'required|numeric|min:0',
            'product_units.*.conversion_factor' =>
                'nullable|numeric|min:0.001',
        ]);

        $price = (float) ($request->price ?? 0);
        if ($request->filled('product_units')) {
            $defaultUnit = collect($request->product_units)
                ->firstWhere('is_default', true);
            if ($defaultUnit) {
                $price = (float)$defaultUnit['price'];
            }
        }

        $cost = $request->filled('cost')
            ? (float) $request->cost
            : round($price / 1.16, 2);

        $product = null;

        DB::transaction(function () use ($request, $cost, $price, &$product) {

            $product = Product::create([

                'business_id' => auth()->user()->business_id,
                'category_id' => $request->category_id,
                'tax_id' => 1,
                'name' => $request->name,
                'brand' => $request->brand,
                'slug' => Str::slug($request->name),
                'sku' => $request->sku,
                'barcode' => $request->barcode,
                'description' => $request->description,
                'cost' => $cost,
                'price' => $price,
                'wholesale_price' => $request->wholesale_price ?? 0,
                'stock_alert' => $request->stock_alert ?? 0,
                'track_stock' => $request->track_stock ?? true,
                'has_variants' => $request->has_variants ?? false,
                'status' => $request->status ?? true,
                'image' => $request->image,

            ]);

            // Crear unidades de venta
            if ($request->filled('product_units')) {
                foreach ($request->product_units as $unit) {
                    if (!$unit['sale_type_id']) {
                        continue;
                    }
                    ProductUnit::create([
                        'product_id' => $product->id,
                        'sale_type_id' => $unit['sale_type_id'],
                        'conversion_factor' =>
                            $unit['conversion_factor'] ?? 1,
                        'price' => $unit['price'],
                        'is_default' =>
                            $unit['is_default'] ?? false,
                        'created_by' => auth()->id(),
                    ]);
                }

                // Sincronizar precio viejo del producto
                $defaultUnit = collect($request->product_units)
                    ->firstWhere('is_default', true);

                if ($defaultUnit) {
                    $product->update([
                        'price' => $defaultUnit['price']
                    ]);
                }
            }

            $branchId = auth()->user()->branch_id ?? 1;
            $initialStock = $request->stock ?? 0;

            $inventory = Inventory::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'branch_id' => $branchId,
                ],
                [
                    'product_variant_id' => null,
                    'stock' => $initialStock,
                ]
            );


            // Registrar movimiento solo si hay stock inicial
            if ($initialStock > 0) {

                InventoryMovement::create([
                    'branch_id'             => $branchId,
                    'product_id'            => $product->id,
                    'product_variant_id'    => null,
                    'type'                  => 'purchase',
                    'quantity'              => $initialStock,
                    'reference_type'        => 'product',
                    'reference_id'          => $product->id,
                    'notes'                 => 'Stock inicial al crear producto',
                    'created_by'            => auth()->id(),
                ]);

            }
        });

        $product->load('inventories');

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto creado e inventario registrado correctamente.');
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
    public function edit(Product $product)
    {
        $product->load([
            'inventories.branch',
            'productUnits.saleType',
            'packageOpenings',
            'variants'
        ]);

        return inertia('Products/Edit', [
            'product' => $product,
            'categories' => Category::all(),
            'taxes' => Tax::all(),
            'saleTypes' => SaleType::orderBy('id')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // 1. Validar campos
        $validated = $request->validate([
            'category_id'     => 'required|exists:categories,id',
            'tax_id'          => 'nullable|exists:taxes,id',
            'sku'             => 'required|string|max:100|unique:products,sku,' . $product->id,
            'barcode'         => 'nullable|string|max:100|unique:products,barcode,' . $product->id,
            'name'            => 'required|string|max:255',
            'slug'            => 'required|string|max:255|unique:products,slug,' . $product->id,
            'description'     => 'nullable|string',
            'image'           => 'nullable|string',
            'cost'            => 'required|numeric|min:0',
            'price'           => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'add_stock'       => 'nullable|integer|min:0',
            'stock_alert'     => 'nullable|integer|min:0',
            'track_stock'     => 'boolean',
            'has_variants'    => 'boolean',
            'status'          => 'boolean',
        ]);

        // 2. Operación Atómica
        DB::transaction(function () use ($product, $validated) {

            $quantityToAdd = $validated['add_stock'] ?? 0;

            // Si se envió una cantidad mayor a 0 para agregar stock
            if ($quantityToAdd > 0) {
                $branchId = auth()->user()->branch_id ?? 1;

                // 1. Busca el registro existente o crea uno nuevo con stock inicial en 0
                $inventory = Inventory::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'branch_id'  => $branchId,
                    ],
                    [
                        'stock'     => 0,
                        'type'      => 'in',
                        'reference' => 'Ajuste manual desde edición de producto',
                        'user_id'   => auth()->id(),
                    ]
                );

                $previousStock = $inventory->stock;


                // Actualizar stock
                $inventory->increment('stock', $quantityToAdd);


                // Nuevo stock después del incremento
                $newStock = $inventory->fresh()->stock;


                // Registrar movimiento
                InventoryMovement::create([
                    'branch_id'             => $branchId,
                    'product_id'            => $product->id,
                    'product_variant_id'    => null,
                    'type'                  => 'adjustment',
                    'quantity'              => $quantityToAdd,
                    'reference_type'        => 'product',
                    'reference_id'          => $product->id,
                    'notes'                 => 'Ajuste manual desde edición de producto. Stock anterior: ' . $previousStock . ' Stock nuevo: ' . $newStock,
                    'created_by'            => auth()->id(),
                ]);
            }

            // Actualizamos el resto de campos del producto (excluyendo add_stock)
            unset($validated['add_stock']);
            $product->update($validated);
        });

        if ($request->has('deleted_units')) {
            ProductUnit::whereIn(
                'id',
                $request->deleted_units
            )
            ->where('product_id',$product->id)
            ->delete();
        }

        if ($request->has('product_units')) {
            foreach ($request->product_units as $unit) {
                if (!empty($unit['id'])) {
                    $productUnit = ProductUnit::find($unit['id']);
                    if ($productUnit) {
                        $productUnit->update([
                            'sale_type_id' => $unit['sale_type_id'],
                            'conversion_factor' => $unit['conversion_factor'] ?? 1,
                            'price' => $unit['price'],
                            'is_default' => $unit['is_default'] ?? false,
                        ]);
                    }
                } else {
                    ProductUnit::create([
                        'product_id' => $product->id,
                        'sale_type_id' => $unit['sale_type_id'],
                        'conversion_factor' => $unit['conversion_factor'] ?? 1,
                        'price' => $unit['price'],
                        'is_default' => $unit['is_default'] ?? false,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
            $defaultUnit = collect($request->product_units)
                ->firstWhere('is_default', true);
            if ($defaultUnit) {
                $product->update([
                    'price' => $defaultUnit['price']
                ]);
            }
        }

        $product->load('inventories');

        return redirect()->back()->with('success', 'Producto e inventario actualizados correctamente.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->back()
            ->with('success', 'Producto eliminado correctamente.');
    }

    // public function searchBarcode(Request $request)
    // {
    //     $service = new OpenFoodFactsService();

    //     return response()->json(
    //         $service->search($request->barcode)
    //     );
    // }

    public function searchBarcode(Request $request)
    {
        $service = new OpenFoodFactsService();
        $data = $service->search($request->barcode);
        $resolver = new CategoryResolverService();

        $categoryId = $resolver->resolve(
            $data['categories'] ?? []
        );

        $data['category_id'] = $categoryId;
        return response()->json($data);
    }

    /**
     * Importar productos desde archivo Excel/CSV.
     */
    public function import(Request $request)
    {
        set_time_limit(0);
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new ProductsImport();
            Excel::import($import, $request->file('file'));

            $failures = $import->errors();

            if ($failures->count() > 0) {
                $errorMessages = [];
                foreach ($failures as $failure) {
                    foreach ($failure->errors() as $error) {
                        $errorMessages[] = $error;
                    }
                }

                // Si hubo errores controlados en algunas filas
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo contiene errores en algunas filas.',
                    'errors'  => $errorMessages
                ], 200);
            }

            // Si todo salió bien
            return response()->json([
                'success' => true,
                'message' => 'Productos e inventarios importados correctamente.'
            ], 200);

        } catch (\Exception $e) {
            // Errores no controlados (ej. archivo dañado o error de BD)
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar el archivo.',
                'errors'  => [$e->getMessage()]
            ], 500);
        }
    }
}
