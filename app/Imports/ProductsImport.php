<?php
namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\ProductPrice;
use App\Services\OpenFoodFactsService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Validators\Failure;
use App\Models\ProductUnit;
use App\Models\ProductPackageOpening;
use App\Models\SaleType;

class ProductsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    SkipsOnError
{
    use SkipsErrors;

    protected OpenFoodFactsService $offService;

    public function __construct()
    {
        $this->offService = new OpenFoodFactsService();
    }

    public function model(array $row)
    {
        // Código del producto
        $barcode = isset($row['clave1'])
            ? trim((string)$row['clave1'])
            : null;

        // Descripción / Nombre del archivo
        $name = isset($row['descripcion'])
            ? trim($row['descripcion'])
            : null;

        if (empty($barcode) || empty($name)) {
            return null;
        }

        // Evitar duplicados
        $exists = Product::where('barcode', $barcode)
            ->orWhere('sku', $barcode)
            ->first();

        if ($exists) {
            $this->errors()->push(new Failure(
                0,
                'clave1',
                ["Código de barras / SKU ya existente: {$barcode} - {$name}"]
            ));

            return null;
        }

        $businessId = auth()->user()->business_id ?? 1;
        $branchId   = auth()->user()->branch_id ?? 1;
        $userId     = auth()->id() ?? 1;

        /*
        |--------------------------------------------------------------------------
        | Consulta OpenFoodFacts (Imagen, Marca, Descripción)
        |--------------------------------------------------------------------------
        */
        $imageUrl    = null;
        $brandName   = isset($row['marca']) ? trim($row['marca']) : null;
        $description = isset($row['observaciones']) ? trim($row['observaciones']) : null;

        try {
            $offData = $this->offService->search($barcode);

            if (!empty($offData)) {
                // Asignar imagen si la encontró
                $imageUrl = $offData['image'] ?? null;

                // Si no viene marca en el Excel, tomar la de OpenFoodFacts
                if (empty($brandName) && !empty($offData['brand'])) {
                    $brandName = Str::limit($offData['brand'], 255);
                }

                // Si no viene descripción en el Excel, tomar la de OpenFoodFacts
                if (empty($description) && !empty($offData['description'])) {
                    $description = $offData['description'];
                }
            }
        } catch (\Throwable $e) {
            // Si la llamada falla o el código no existe, continúa silenciosamente
            $imageUrl = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Categoría
        |--------------------------------------------------------------------------
        */
        $categoryId = null;

        if (!empty($row['categoria'])) {
            $categoryName = trim($row['categoria']);

            $category = Category::firstOrCreate(
                [
                    'name' => $categoryName,
                    'business_id' => $businessId
                ],
                [
                    'slug' => Str::slug($categoryName)
                ]
            );

            $categoryId = $category->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Valores del producto
        |--------------------------------------------------------------------------
        */
        $cost           = isset($row['costo']) ? (float)$row['costo'] : 0;
        $price          = isset($row['precio1']) ? (float)$row['precio1'] : 0;
        $wholesalePrice = isset($row['precio2']) ? (float)$row['precio2'] : (isset($row['precio3']) ? (float)$row['precio3'] : 0);
        $stockAlert     = isset($row['inv_min']) ? (float)$row['inv_min'] : 0;
        $initialStock   = isset($row['existencia']) ? (float)$row['existencia'] : 0;
        $unit           = $row['unidad'] ?? null;
        // $tipoVenta      = strtolower(trim($row['tipo_venta'] ?? 'unit'));
        $tipoVenta      = strtolower(trim($row['tipo_venta'] ?? ''));

        if(empty($tipoVenta)){

            $tipoVenta = match(strtolower(trim($unit))){

                'KILOGRAMO',
                'kilogramo',
                'KG',
                'kg'
                    => 'kg',

                'PAQ',
                'paq',
                'PAQUETE',
                'paquete'
                    => 'package',

                default
                    => 'unit'
            };

        }

        $trackStock = (isset($row['sn_inventariable']) && strtolower(trim($row['sn_inventariable'])) === 's') ? 1 : 0;
        $isWeighted = (isset($row['sn_granel']) && strtolower(trim($row['sn_granel'])) === 's') ? 1 : 0;

        $taxId = 1; // IVA 0% por defecto
        if (isset($row['sn_imp_iva_16']) && strtolower(trim($row['sn_imp_iva_16'])) === 's') {
            $taxId = 2; // IVA 16%
        }

        $saleTypeCode = match ($tipoVenta) {
            'kg',
            'kilogramo',
            'kilo'
                => 'kg',
            'meter',
            'metro',
            'm'
                => 'meter',
            'package',
            'paq',
            'paquete'
                => 'package',
            default
                => 'unit'
        };

        try {
            return DB::transaction(function () use (
                $businessId,
                $branchId,
                $userId,
                $categoryId,
                $barcode,
                $name,
                $brandName,
                $description,
                $imageUrl,
                $cost,
                $price,
                $wholesalePrice,
                $stockAlert,
                $initialStock,
                $isWeighted,
                $trackStock,
                $taxId,
                $unit,
                $tipoVenta,
                $saleTypeCode,
            ) {

                /*
                |--------------------------------------------------------------------------
                | Crear producto asignando exactamente las columnas de la BD
                |--------------------------------------------------------------------------
                */
                $product = Product::create([
                    'business_id'     => $businessId,
                    'category_id'     => $categoryId,
                    'tax_id'          => $taxId,
                    'sku'             => $barcode,
                    'barcode'         => $barcode,
                    'name'            => $name,
                    'brand'           => $brandName,
                    'slug'            => Str::slug($name) . '-' . Str::random(5),
                    'description'     => $description,
                    'image'           => $imageUrl, // Guarda la URL de la imagen o NULL
                    'cost'            => $cost,
                    'price'           => $price,
                    'wholesale_price' => $wholesalePrice,
                    'stock_alert'     => $stockAlert,
                    'track_stock'     => $trackStock,
                    'has_variants'    => 0,
                    'is_weighted'     => $isWeighted,
                    'unit'            => $unit,
                    'status'          => 1,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Unidad de venta
                |--------------------------------------------------------------------------
                */

                $saleType = SaleType::where('code',$saleTypeCode)->first();

                if($saleType){
                    ProductUnit::create([
                        'product_id' => $product->id,
                        'sale_type_id' => $saleType->id,
                        'conversion_factor' => 1,
                        'price' => $price,
                        'is_default' => 1,
                    ]);
                }

                if(
                    $saleTypeCode === 'package'
                ){

                    /*
                    No sabemos cuántas piezas tiene.
                    Se registra cuando el empleado abra el paquete.
                    */

                }

                /*
                |--------------------------------------------------------------------------
                | Historial de precios
                |--------------------------------------------------------------------------
                */
                ProductPrice::create([
                    'product_id' => $product->id,
                    'old_price'  => 0,
                    'new_price'  => $price,
                    'changed_by' => $userId,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Inventario inicial
                |--------------------------------------------------------------------------
                */
                Inventory::create([
                    'product_id' => $product->id,
                    'branch_id'  => $branchId,
                    'product_variant_id' => null,
                    'stock'      => $initialStock,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Movimiento inicial
                |--------------------------------------------------------------------------
                */
                if ($initialStock > 0) {
                    InventoryMovement::create([
                        'branch_id'      => $branchId,
                        'product_id'     => $product->id,
                        'product_variant_id'     => null,
                        'type'           => 'purchase',
                        'quantity'       => $initialStock,
                        'reference_type' => 'product_import',
                        'reference_id'   => $product->id,
                        'notes'          => 'Stock inicial desde importación',
                        'created_by'     => $userId,
                    ]);
                }

                return $product;
            });

        } catch (\Exception $e) {
            $this->errors()->push(new Failure(
                0,
                'excel',
                ["Error al procesar producto '{$name}': " . $e->getMessage()]
            ));

            return null;
        }
    }

    public function rules(): array
    {
        return [
            '*.clave1' => ['required'],
            '*.descripcion' => ['required'],
            '*.precio1' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.clave1.required' => 'El código/clave1 es obligatorio.',
            '*.descripcion.required' => 'La descripción es obligatoria.',
            '*.precio1.required' => 'El precio1 es obligatorio.',
            '*.precio1.numeric' => 'El precio1 debe ser numérico.',
            '*.precio1.min' => 'El precio1 no puede ser negativo.',
        ];
    }

    public function chunkSize(): int
    {
        return 50;
    }
}
