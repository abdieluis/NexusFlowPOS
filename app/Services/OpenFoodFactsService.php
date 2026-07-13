<?php

namespace App\Services;

use App\Models\CategorySource;
use Illuminate\Support\Facades\Http;

class OpenFoodFactsService
{
    /**
     * Buscar un producto por código de barras.
     */
    public function search(string $barcode): array
    {
        $response = Http::timeout(10)
            ->get("https://world.openfoodfacts.org/api/v2/product/{$barcode}.json");


        if (!$response->successful()) {
            return [];
        }


        $product = $response->json('product');


        if (!$product) {
            return [];
        }


        $categories = $product['categories_tags'] ?? [];


        return [
            'barcode'     => $barcode,
            'name'        => $product['product_name'] ?? '',
            'brand'       => $product['brands'] ?? '',
            'description' => $product['generic_name'] ?? '',
            'image'       => $product['image_front_url'] ?? '',

            'category_id' => $this->resolveCategory($categories),

            // importante para cuando no exista relación
            'categories' => $this->cleanCategories($categories),
        ];
    }


    /**
     * Buscar categoría aprendida.
     */
    private function resolveCategory(array $categories): ?int
    {
        foreach ($categories as $category) {


            // quitamos idioma
            $category = str_replace('en:', '', $category);


            $relation = CategorySource::where(
                'source_name',
                $category
            )->first();


            if ($relation) {
                return $relation->category_id;
            }
        }


        return null;
    }


    /**
     * Limpiar categorías de OpenFoodFacts.
     */
    private function cleanCategories(array $categories): array
    {
        return collect($categories)
            ->map(function ($category) {

                return str_replace('en:', '', $category);

            })
            ->values()
            ->toArray();
    }
}
