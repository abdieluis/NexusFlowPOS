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

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')
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
            'taxes' => $taxes
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
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string|max:1000',

        ]);

        $price = (float) $request->price;
        $cost = $request->filled('cost')
            ? (float) $request->cost
            : round($price / 1.16, 2);

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


        /*
        |--------------------------------------------------------------------------
        | Si viene desde Vue/Axios regresamos JSON
        |--------------------------------------------------------------------------
        */

        if ($request->expectsJson()) {

            return response()->json([
                'success' => true,
                'message' => 'Producto creado correctamente.',
                'product' => $product->load('category')
            ], 201);
        }


        /*
        |--------------------------------------------------------------------------
        | Si viene del formulario tradicional Inertia
        |--------------------------------------------------------------------------
        */

        // return redirect()
        //     ->back()
        //     ->with('success', 'Producto creado correctamente');
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
        // Cargamos los inventarios del producto con sus respectivas sucursales
        $product->load(['inventories.branch']);

        return inertia('Products/Edit', [
            'product'    => $product,
            'categories' => Category::all(),
            'taxes'      => Tax::all(),
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

                // 2. Suma la nueva cantidad al stock existente en esa sucursal
                $inventory->increment('stock', $quantityToAdd);

                // Opcional: Actualizar el usuario o la referencia del último movimiento
                $inventory->update([
                    'user_id'   => auth()->id(),
                    'reference' => 'Ajuste manual desde edición de producto',
                ]);
            }

            // Actualizamos el resto de campos del producto (excluyendo add_stock)
            unset($validated['add_stock']);
            $product->update($validated);
        });

        $product->load('inventories');

        return redirect()->back()->with('success', 'Producto e inventario actualizados correctamente.');
    }

    public function searchBarcode(Request $request)
    {
        $service = new OpenFoodFactsService();

        return response()->json(
            $service->search($request->barcode)
        );
    }
}
