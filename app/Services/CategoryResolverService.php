<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategorySource;

class CategoryResolverService
{

    /**
     * Categorías que no queremos aprender
     * porque son demasiado generales
     */
    private array $ignoredCategories = [

        'food',
        'foods',
        'products',
        'product',
        'groceries',
        'snacks',

        'beverages',
        'beverage',
        'beverages-and-beverages-preparations',
        'non-alcoholic-beverages',
    ];


    public function resolve($categories)
    {

        if(empty($categories)){
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | 1. Buscar primero si ya existe aprendizaje
        |--------------------------------------------------------------------------
        */

        foreach($categories as $sourceName)
        {

            $sourceName = strtolower(trim($sourceName));


            if($this->isIgnored($sourceName)){
                continue;
            }


            $source = CategorySource::where(
                    'source',
                    'openfoodfacts'
                )
                ->where(
                    'source_name',
                    $sourceName
                )
                ->first();


            if($source)
            {
                return $source->category_id;
            }

        }



        /*
        |--------------------------------------------------------------------------
        | 2. Si no existe, intentar aprender
        |--------------------------------------------------------------------------
        */

        return $this->learn($categories);

    }





    private function learn($categories)
    {

        $dictionary =
            OpenFoodFactsCategoryMapper::dictionary();



        foreach($dictionary as $categoryName=>$words)
        {


            foreach($categories as $sourceName)
            {

                $sourceName = strtolower(trim($sourceName));



                /*
                |--------------------------------------------------------------------------
                | Encontramos coincidencia en nuestro diccionario
                |--------------------------------------------------------------------------
                */

                if(in_array($sourceName,$words))
                {


                    $category = Category::where(
                        'name',
                        $categoryName
                    )
                    ->first();



                    if($category)
                    {


                        /*
                        |--------------------------------------------------------------------------
                        | Guardamos solamente categorías útiles
                        |--------------------------------------------------------------------------
                        */

                        foreach($categories as $item)
                        {

                            $item = strtolower(trim($item));


                            if($this->isIgnored($item)){
                                continue;
                            }



                            CategorySource::firstOrCreate(
                                [
                                    'category_id'=>$category->id,
                                    'source'=>'openfoodfacts',
                                    'source_name'=>$item
                                ]
                            );

                        }



                        return $category->id;

                    }

                }

            }

        }



        return null;

    }





    private function isIgnored($category)
    {

        return in_array(
            strtolower(trim($category)),
            $this->ignoredCategories
        );

    }

}
