<?php

namespace App\Services;

class OpenFoodFactsCategoryMapper
{

    public static function dictionary()
    {
        return [

            'Refrescos' => [
                'soft-drinks',
                'sodas',
                'colas',
                'carbonated-drinks'
            ],

            'Cerveza' => [
                'beers',
                'beer'
            ],

            'Leche' => [
                'milks',
                'milk'
            ],

            'Pan' => [
                'breads',
                'bread'
            ],

        ];
    }

}
