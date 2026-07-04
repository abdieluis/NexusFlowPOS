<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Validators\Failure;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnError
{
    use SkipsErrors;

    public function model(array $row)
    {
        // 1. Limpiar e identificar la llave única del CSV
        $barcode = isset($row['codigo_barras']) ? trim($row['codigo_barras']) : null;

        if (empty($barcode)) {
            $this->errors()->push(new Failure(
                0, 'codigo_barras', ['Fila omitida: El código de barras es requerido.']
            ));
            return null;
        }

        // Evitar duplicados
        $exists = Product::where('barcode', $barcode)->orWhere('sku', $barcode)->first();

        if ($exists) {
            $this->errors()->push(new Failure(
                $barcode,
                'codigo_barras',
                ["Código de barras/SKU duplicado en el sistema: {$barcode} - {$row['nombre']}"]
            ));
            return null;
        }

        // 2. Resolver la Categoría automáticamente
        $categoryId = null;
        if (!empty($row['categoria'])) {
            $categoryName = trim($row['categoria']);
            $businessId = auth()->user()->business_id ?? 1;

            $category = Category::firstOrCreate(
                ['name' => $categoryName, 'business_id' => $businessId],
                ['slug' => Str::slug($categoryName), 'business_id' => $businessId]
            );
            $categoryId = $category->id;
        }

        // 3. 👇 INTENTAR CREAR EL PRODUCTO PROTEGIENDO CONTRA ERRORES SQL
        try {
            return new Product([
                'business_id' => auth()->user()->business_id ?? 1,
                'name'        => $row['nombre'],
                'sku'         => $barcode,
                'barcode'     => $barcode,
                'cost'        => $row['precio'] ?? 0,
                'price'       => $row['precio_neto'] ?? ($row['precio'] * 1.16),
                'tax_id'      => 1, // Tu ID de IVA por defecto
                'category_id' => $categoryId,
                'slug'        => !empty($row['slug']) ? $row['slug'] : Str::slug($row['nombre']),
                'image'       => null,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Si la base de datos rechaza la fila, la guardamos como un error controlado
            $this->errors()->push(new Failure(
                $barcode,
                'base_de_datos',
                ["Error en producto '{$row['nombre']}': Revisa si faltan campos obligatorios en tu BD. Detalle: " . $e->getMessage()]
            ));
            return null;
        }
    }

    // Reglas de validación adaptadas a los nombres de las columnas del CSV
    public function rules(): array
    {
        return [
            'nombre'        => 'required|string|max:255',
            'codigo_barras' => 'required',
            'precio'        => 'required|numeric|min:0',
        ];
    }

    // Mensajes personalizados para los errores
    public function customValidationMessages()
    {
        return [
            'nombre.required'        => 'El campo "nombre" es obligatorio en el archivo.',
            'codigo_barras.required' => 'El campo "codigo_barras" es obligatorio en el archivo.',
            'precio.required'        => 'El campo "precio" es obligatorio.',
            'precio.numeric'         => 'El "precio" debe ser un número válido.',
        ];
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
