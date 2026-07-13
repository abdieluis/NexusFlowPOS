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
            'products' => $products
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
        return inertia('Products/Edit', [
            'product' => $product,
            'categories' => Category::all(),
            'taxes' => Tax::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'barcode' => 'nullable|string',
        ]);

        $product->update([
            'name' => $request->name,
            'price' => $request->price,
            'category_id' => $request->category_id,
            'barcode' => $request->barcode,
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Producto eliminado');
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        $import = new ProductsImport;

        try {
            Excel::import($import, $request->file('file'));

            // Leer los errores acumulados por el importador (tanto duplicados como de SQL)
            if (method_exists($import, 'errors') && $import->errors()->isNotEmpty()) {
                $errorMessages = [];
                foreach ($import->errors() as $failure) {
                    $errorMessages = array_merge($errorMessages, $failure->errors());
                }

                return response()->json([
                    'success' => false,
                    'errors' => $errorMessages
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Productos importados correctamente.'
            ]);

        } catch (\Throwable $e) {
            // Cualquier error masivo que rompa el archivo por completo (ej: archivo corrupto)
            return response()->json([
                'success' => false,
                'errors' => ['No se pudo procesar el archivo: ' . $e->getMessage()]
            ], 500);
        }
    }

    public function searchBarcode(Request $request)
    {
        $service = new OpenFoodFactsService();

        return response()->json(
            $service->search($request->barcode)
        );
    }
}
