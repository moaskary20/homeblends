<?php

return [
    /**
     * Main storefront departments (homepage circles + nav roots).
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
            'description' => 'سيراميك وبورسلين وتشطيبات للأرضيات والجدران',
            'image' => 'images/categories/ceramics.jpg',
        ],
        [
            'name' => 'الأجهزة المنزلية',
            'slug' => 'home-appliances',
            'sort_order' => 3,
            'description' => 'أجهزة كهربائية منزلية كبيرة وصغيرة',
            'image' => 'images/categories/home-appliances.jpg',
        ],
        [
            'name' => 'صحي',
            'slug' => 'sanitary',
            'sort_order' => 4,
            'description' => 'أدوات صحية وخزانات دفن ووحدات حمام',
            'image' => 'images/categories/sanitary.jpg',
        ],
        [
            'name' => 'لوحات فنية',
            'slug' => 'art-panels',
            'sort_order' => 5,
            'description' => 'لوحات فنية وديكور حائط يضيف لمسة أناقة',
            'image' => 'images/categories/art-panels.jpg',
        ],
        [
            'name' => 'اضاءات',
            'slug' => 'lighting',
            'sort_order' => 6,
            'description' => 'إضاءات داخلية وخارجية لكل أركان المنزل',
            'image' => 'images/categories/lighting.jpg',
        ],
        [
            'name' => 'سجاد',
            'slug' => 'carpets',
            'sort_order' => 7,
            'description' => 'سجاد وموكيت بأشكال وألوان تناسب ديكورك',
            'image' => 'images/categories/carpets.jpg',
        ],
        [
            'name' => 'منسوجات',
            'slug' => 'textiles',
            'sort_order' => 8,
            'description' => 'مفروشات، ملايات، وستائر بجودة فاخرة',
            'image' => 'images/categories/textiles.jpg',
        ],
    ],

    /**
     * Live photo sources (Unsplash) keyed by category slug.
     */
    'category_image_sources' => [
        'athath' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=900&q=80&auto=format',
        'ceramics' => 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?w=900&q=80&auto=format',
        'home-appliances' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?w=900&q=80&auto=format',
        'sanitary' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?w=900&q=80&auto=format',
        'art-panels' => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=900&q=80&auto=format',
        'lighting' => 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?w=900&q=80&auto=format',
        'carpets' => 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=900&q=80&auto=format',
        'textiles' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=900&q=80&auto=format',
        'living-room' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=900&q=80&auto=format',
        'bedrooms' => 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?w=900&q=80&auto=format',
        'dining-rooms' => 'https://images.unsplash.com/photo-1617806118233-18e1de247200?w=900&q=80&auto=format',
        'salons' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=900&q=80&auto=format',
        'outdoor' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=900&q=80&auto=format',
        'libraries' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=900&q=80&auto=format',
        'indoor-flooring' => 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?w=900&q=80&auto=format',
        'walls' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=900&q=80&auto=format',
        'outdoor-flooring' => 'https://images.unsplash.com/photo-1600607687644-c7171b42498f?w=900&q=80&auto=format',
        'porcelain' => 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=900&q=80&auto=format',
        'mixers' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?w=900&q=80&auto=format',
        'kitchen-mixers' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=900&q=80&auto=format',
        'bathroom-mixers' => 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=900&q=80&auto=format',
        'sanitary-equipment' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?w=900&q=80&auto=format',
        'basins' => 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=900&q=80&auto=format',
        'combination' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=900&q=80&auto=format',
        'bathtub-sets' => 'https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=900&q=80&auto=format',
        'jacuzzi' => 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=900&q=80&auto=format',
        'concealed-sanitary-sets' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=900&q=80&auto=format',
        'sanitary-supplies' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=900&q=80&auto=format',
    ],

    /**
     * Nested storefront subcategories under صحي.
     */
    'sanitary_subcategories' => [
        'mixers' => [
            'name' => 'خلاطات',
            'sort_order' => 1,
            'description' => 'خلاطات مطابخ وحمامات بجودة عالية',
            'image' => 'images/categories/mixers.jpg',
            'children' => [
                'kitchen-mixers' => [
                    'name' => 'مطابخ',
                    'sort_order' => 1,
                    'description' => 'خلاطات ومغاسل المطبخ',
                    'image' => 'images/categories/kitchen-mixers.jpg',
                ],
                'bathroom-mixers' => [
                    'name' => 'حمامات',
                    'sort_order' => 2,
                    'description' => 'خلاطات البانيو والشاور والأحواض',
                    'image' => 'images/categories/bathroom-mixers.jpg',
                ],
            ],
        ],
        'sanitary-equipment' => [
            'name' => 'أجهزة صحية',
            'sort_order' => 2,
            'description' => 'أحواض وبانيوهات ووحدات صحية متكاملة',
            'image' => 'images/categories/sanitary-equipment.jpg',
            'children' => [
                'basins' => [
                    'name' => 'أحواض',
                    'sort_order' => 1,
                    'description' => 'أحواض حمامات وديكور',
                    'image' => 'images/categories/basins.jpg',
                ],
                'combination' => [
                    'name' => 'كوبنشن',
                    'sort_order' => 2,
                    'description' => 'وحدات صحية متكاملة وكومباكت',
                    'image' => 'images/categories/combination.jpg',
                ],
                'bathtub-sets' => [
                    'name' => 'بانيوهات',
                    'sort_order' => 3,
                    'description' => 'بانيوهات وأطقم استحمام',
                    'image' => 'images/categories/bathtub-sets.jpg',
                ],
                'jacuzzi' => [
                    'name' => 'جاكوزي',
                    'sort_order' => 4,
                    'description' => 'جاكوزي وبانيوهات فاخرة',
                    'image' => 'images/categories/jacuzzi.jpg',
                ],
            ],
        ],
        'concealed-sanitary-sets' => [
            'name' => 'أطقم صحية دفن',
            'sort_order' => 3,
            'description' => 'خزانات دفن وأنظمة صحية مخفية',
            'image' => 'images/categories/concealed-sanitary-sets.jpg',
            'children' => [],
        ],
        'sanitary-supplies' => [
            'name' => 'لوازم أدوات صحية',
            'sort_order' => 4,
            'description' => 'مراحيض ودش ولوازم تكميلية',
            'image' => 'images/categories/sanitary-supplies.jpg',
            'children' => [],
        ],
    ],

    /**
     * Storefront subcategories under أثاث / سيراميك.
     */
    'department_subcategories' => [
        'athath' => [
            'living-room' => [
                'name' => 'ليفينج روم',
                'sort_order' => 1,
                'description' => 'أثاث غرف المعيشة والجلوس',
                'image' => 'images/categories/living-room.jpg',
            ],
            'bedrooms' => [
                'name' => 'غرف نوم',
                'sort_order' => 2,
                'description' => 'أسرة وخزائن وغرف نوم كاملة',
                'image' => 'images/categories/bedrooms.jpg',
            ],
            'dining-rooms' => [
                'name' => 'غرف سفره',
                'sort_order' => 3,
                'description' => 'سفرة وكراسي وبوفيهات',
                'image' => 'images/categories/dining-rooms.jpg',
            ],
            'salons' => [
                'name' => 'صلونات',
                'sort_order' => 4,
                'description' => 'صالونات ومجالس ضيافة',
                'image' => 'images/categories/salons.jpg',
            ],
            'outdoor' => [
                'name' => 'اوت دور',
                'sort_order' => 5,
                'description' => 'أثاث خارجي للحدائق والتراس',
                'image' => 'images/categories/outdoor.jpg',
            ],
            'libraries' => [
                'name' => 'مكتبات',
                'sort_order' => 6,
                'description' => 'مكتبات ووحدات تخزين للكتب',
                'image' => 'images/categories/libraries.jpg',
            ],
        ],
        'ceramics' => [
            'indoor-flooring' => [
                'name' => 'أرضيات داخليه',
                'sort_order' => 1,
                'description' => 'سيراميك وبورسلين للأرضيات الداخلية',
                'image' => 'images/categories/indoor-flooring.jpg',
            ],
            'walls' => [
                'name' => 'حوائط',
                'sort_order' => 2,
                'description' => 'تشطيبات وجدران سيراميك وبورسلين',
                'image' => 'images/categories/walls.jpg',
            ],
            'outdoor-flooring' => [
                'name' => 'أرضيات خارجيه',
                'sort_order' => 3,
                'description' => 'أرضيات خارجية مقاومة للعوامل الجوية',
                'image' => 'images/categories/outdoor-flooring.jpg',
            ],
            'porcelain' => [
                'name' => 'بورسلين',
                'sort_order' => 4,
                'description' => 'بورسلين فاخر للأرضيات والحوائط',
                'image' => 'images/categories/porcelain.jpg',
            ],
        ],
    ],

    /**
     * Unified subcategories under home-appliances (shared by all appliance scrapers).
     */
    'home_appliances' => [
        'refrigerators' => 'ثلاجات',
        'washing-machines' => 'غسالات ملابس',
        'air-conditioners' => 'تكييفات',
        'cookers' => 'بوتاجازات',
        'freezers' => 'فريزر',
        'dishwashers' => 'غسالات أطباق',
        'water-heaters' => 'سخانات',
        'televisions' => 'تلفزيونات',
        'small-appliances' => 'أجهزة صغيرة',
        'kitchen-appliances' => 'أجهزة مطبخ',
        'air-cooler' => 'مبرد هواء صحراوي',
        'large-appliances' => 'أجهزة كبيرة',
        'food-processors' => 'محضرات طعام',
        'vacuum' => 'مكانس كهربائية',
        'kettle' => 'غلايات',
        'microwave' => 'ميكروويف',
        'irons-and-steamers' => 'مكاوي',
        'water-dispensers' => 'برادات مياه',
        'water-filters' => 'فلاتر مياه',
        'fans' => 'مراوح',
        'hoods-exhaust-fans' => 'شفاطات ومراوح',
        'electric-radiator' => 'دفايات',
    ],

    /** Unified Mahgoub ceramics subcategories (under سيراميك). */
    'mahgoub_ceramics' => [
        'all' => 'سيراميك و بورسلين',
        'imported' => 'المستورد',
        'local' => 'المحلي',
        'brand-aljohra' => 'الجوهرة',
        'brand-porcelainosa' => 'بورسالينوزا',
        'brand-grespania' => 'جرسبانيا',
        'brand-rako' => 'راك',
        'brand-ceifi' => 'سيفري',
        'brand-spain' => 'صنع في إسبانيا',
        'floor-porcelain' => 'بورسلين أرضيات',
        'wall-porcelain' => 'بورسلين حوائط',
        'floor-ceramic' => 'سيراميك أرضيات',
        'wall-ceramic' => 'سيراميك حوائط',
    ],

    /** Unified Mahgoub sanitary subcategories (under صحي). */
    'mahgoub_sanitary' => [
        'sanitary-all' => 'صحي ووحدات',
        'sanitary-fixtures' => 'أدوات صحية',
        'sanitary-concealed-tanks' => 'خزانات دفن',
        'sanitary-units' => 'وحدات',
        'sanitary-brand-ideal-standard' => 'ايديال ستاندرد',
        'sanitary-brand-grohe' => 'جروهي',
        'sanitary-brand-roca' => 'روكا',
        'sanitary-brand-duravit' => 'ديورافيت',
        'sanitary-brand-geberit' => 'جيبرت',
        'sanitary-brand-hansgrohe' => 'هانز جروهي',
        'sanitary-brand-whitehall' => 'وايت فيل',
        'sanitary-type-toilet' => 'مرحاض',
        'sanitary-type-wall-toilet' => 'مرحاض معلق',
        'sanitary-type-basin' => 'حوض',
        'sanitary-type-bidet' => 'بيديه',
        'sanitary-type-urinal' => 'مبولة',
        'sanitary-type-unit' => 'وحدة',
        'sanitary-type-in-wall-tank' => 'خزان دفن',
        'sanitary-type-seat' => 'سيديلى',
    ],
];
