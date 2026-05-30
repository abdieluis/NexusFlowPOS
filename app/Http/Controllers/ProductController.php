<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Tax;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|max:255',
            'sku' => 'required|unique:products,sku,' . $product->id,
            'price' => 'required|numeric|min:0',
        ]);

        $product->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'description' => $request->description,
            'cost' => $request->cost ?? 0,
            'price' => $request->price,
            'wholesale_price' => $request->wholesale_price ?? 0,
            'stock_alert' => $request->stock_alert ?? 0,
        ]);

        return back()->with('success', 'Producto actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Producto eliminado');
    }
}
