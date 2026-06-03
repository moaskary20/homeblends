<?php

return [
    /**
     * Main storefront departments (homepage circles + nav roots).
     * Images live under public/images/categories/ so they deploy with git.
     */
    'main_departments' => [
        [
            'name' => 'أثاث',
            'slug' => 'athath',
            'sort_order' => 1,
            'description' => 'أثاث داخلي وخارجي لكل غرف المنزل',
            'image' => 'images/categories/athath.jpg',
        ],
        [
            'name' => 'سيراميك',
            'slug' => 'ceramics',
            'sort_order' => 2,
            'description' => 'سيراميك وبورcelain وتشطيبات للأرضيات والجدران',
            'image' => 'images/categories/ceramics.jpg',
        ],
        [
            'name' => 'إكسسوارات',
            'slug' => 'accessories',
            'sort_order' => 3,
            'description' => 'إكسسوارات وديكورات تكمّل أناقة مساحتك',
            'image' => 'images/categories/accessories.jpg',
        ],
        [
            'name' => 'منسوجات',
            'slug' => 'textiles',
            'sort_order' => 4,
            'description' => 'مفروشات، ملايات، وستائر بجودة فاخرة',
            'image' => 'images/categories/textiles.jpg',
        ],
        [
            'name' => 'الأجهزة المنزلية',
            'slug' => 'home-appliances',
            'sort_order' => 5,
            'description' => 'أجهزة كهربائية منزلية كبيرة وصغيرة',
            'image' => 'images/categories/accessories.jpg',
        ],
    ],
];
