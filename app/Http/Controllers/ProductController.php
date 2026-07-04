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
            'name'        => 'required|max:255',
            'sku'         => 'required|max:255|unique:products,sku',
            'price'       => 'required|numeric|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);

        // 1. Crear producto SIN imagen primero
        $product = Product::create([
            'business_id' => 1,
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'sku'         => $request->sku,
            'price'       => $request->price,
            'image'       => null, // aún no
        ]);

        $imagePath = null;

        // 2. Guardar imagen después de tener ID
        if ($request->hasFile('image')) {

            $category = Category::find($request->category_id);

            $folder = 'products/'
                . Str::slug($category->name)
                . '/' . $product->id;

            $file = $request->file('image');

            // nombre: nombreproducto_id.jpg
            $filename = Str::slug($request->name) . '_' . $product->id . '.' . $file->getClientOriginalExtension();

            $imagePath = $file->storeAs($folder, $filename, 'public');

            // actualizar producto con imagen
            $product->update([
                'image' => $imagePath
            ]);
        }

        return redirect()->back()->with('success', 'Producto creado correctamente');
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

    public function quickCreate(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
        ]);

        $businessId = auth()->user()->business_id;

        $product = Product::firstOrCreate(
            [
                'barcode' => $request->barcode,
                'business_id' => $businessId,
            ],
            [
                'name' => $request->name ?? 'Producto sin nombre',
                'sku' => $request->barcode,
                'price' => $request->price ?? 0,
                'stock' => 1,
                'category_id' => $request->category_id ?? null,
                'business_id' => $businessId,
            ]
        );
        return response()->json($product);
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
}
