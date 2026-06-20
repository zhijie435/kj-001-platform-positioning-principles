<?php

return [

    'models' => [

        'customer_group' => App\Models\CustomerGroup::class,

        'customer' => App\Models\User::class,

    ],

    'table_names' => [

        'customer_groups' => 'customer_groups',

        'model_has_customer_groups' => 'model_has_customer_groups',

    ],

    'column_names' => [

        'customer_group_pivot_key' => null,

        'model_morph_key' => 'model_id',

    ],

    'cache' => [

        'expiration_time' => 86400,

        'key' => 'customer_groups.cache',

        'store' => 'default',

    ],

];
