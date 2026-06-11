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
        [
            'name' => 'صحي',
            'slug' => 'sanitary',
            'sort_order' => 6,
            'description' => 'أدوات صحية وخزانات دفن ووحدات حمام',
            'image' => 'images/categories/sanitary.svg',
        ],
        [
            'name' => 'لوحات فنية',
            'slug' => 'art-panels',
            'sort_order' => 7,
            'description' => 'لوحات فنية وديكور حائط يضيف لمسة أناقة',
            'image' => 'images/categories/art-panels.svg',
        ],
        [
            'name' => 'اضاءات',
            'slug' => 'lighting',
            'sort_order' => 8,
            'description' => 'إضاءات داخلية وخارجية لكل أركان المنزل',
            'image' => 'images/categories/lighting.svg',
        ],
        [
            'name' => 'سجاد',
            'slug' => 'carpets',
            'sort_order' => 9,
            'description' => 'سجاد وموكيت بأشكال وألوان تناسب ديكورك',
            'image' => 'images/categories/carpets.svg',
        ],
    ],

    /**
     * Nested storefront subcategories under صحي.
     * Main groups may contain leaf subcategories (children).
     */
    'sanitary_subcategories' => [
        'mixers' => [
            'name' => 'خلاطات',
            'sort_order' => 1,
            'description' => 'خلاطات مطابخ وحمامات بجودة عالية',
            'image' => 'images/categories/sanitary-mixers.svg',
            'children' => [
                'kitchen-mixers' => [
                    'name' => 'مطابخ',
                    'sort_order' => 1,
                    'description' => 'خلاطات ومغاسل المطبخ',
                    'image' => 'images/categories/sanitary-kitchen-mixers.svg',
                ],
                'bathroom-mixers' => [
                    'name' => 'حمامات',
                    'sort_order' => 2,
                    'description' => 'خلاطات البانيو والشاور والأحواض',
                    'image' => 'images/categories/sanitary-bathroom-mixers.svg',
                ],
            ],
        ],
        'sanitary-equipment' => [
            'name' => 'أجهزة صحية',
            'sort_order' => 2,
            'description' => 'أحواض وبانيوهات ووحدات صحية متكاملة',
            'image' => 'images/categories/sanitary-equipment.svg',
            'children' => [
                'basins' => [
                    'name' => 'أحواض',
                    'sort_order' => 1,
                    'description' => 'أحواض حمامات وديكور',
                    'image' => 'images/categories/sanitary-basins.svg',
                ],
                'combination' => [
                    'name' => 'كوبنشن',
                    'sort_order' => 2,
                    'description' => 'وحدات صحية متكاملة وكومباكت',
                    'image' => 'images/categories/sanitary-combination.svg',
                ],
                'bathtub-sets' => [
                    'name' => 'أطقم بانيوهات',
                    'sort_order' => 3,
                    'description' => 'بانيوهات وأطقم استحمام',
                    'image' => 'images/categories/sanitary-bathtub-sets.svg',
                ],
                'jacuzzi' => [
                    'name' => 'جاكوزي',
                    'sort_order' => 4,
                    'description' => 'جاكوزي وبانيوهات فاخرة',
                    'image' => 'images/categories/sanitary-jacuzzi.svg',
                ],
            ],
        ],
        'concealed-sanitary-sets' => [
            'name' => 'أطقم صحية دفن',
            'sort_order' => 3,
            'description' => 'خزانات دفن وأنظمة صحية مخفية',
            'image' => 'images/categories/sanitary-concealed-sets.svg',
            'children' => [],
        ],
        'sanitary-supplies' => [
            'name' => 'لوازم أدوات صحية',
            'sort_order' => 4,
            'description' => 'مراحيض ودش ولوازم تكميلية',
            'image' => 'images/categories/sanitary-supplies.svg',
            'children' => [],
        ],
    ],

    /**
     * Storefront subcategories under أثاث / سيراميك / إكسسوارات.
     * Images live under public/images/categories/ so they deploy with git.
     */
    'department_subcategories' => [
        'accessories' => [
            'bathroom-accessories' => [
                'name' => 'اكسسورات حمام',
                'sort_order' => 1,
                'description' => 'إكسسوارات وتجهيزات الحمام',
                'image' => 'images/categories/accessories-bathroom-accessories.svg',
            ],
            'kitchen-accessories' => [
                'name' => 'اكسسورات مطابخ',
                'sort_order' => 2,
                'description' => 'إكسسوارات وتجهيزات المطبخ',
                'image' => 'images/categories/accessories-kitchen-accessories.svg',
            ],
            'furniture-accessories' => [
                'name' => 'اكسسوارات الأثاث',
                'sort_order' => 3,
                'description' => 'مقابض وإكسسوارات تكمّل قطع الأثاث',
                'image' => 'images/categories/accessories-furniture-accessories.svg',
            ],
            'door-accessories' => [
                'name' => 'اكسسوارات الابواب',
                'sort_order' => 4,
                'description' => 'مفصلات ومقابض وإكسسوارات الأبواب',
                'image' => 'images/categories/accessories-door-accessories.svg',
            ],
            'doors-kitchen-hardware' => [
                'name' => 'اكسسوارات الابواب والمطابخ',
                'sort_order' => 5,
                'description' => 'حلول موحّدة لأبواب المطبخ والأثاث',
                'image' => 'images/categories/accessories-doors-kitchen-hardware.svg',
            ],
        ],
        'athath' => [
            'living-room' => [
                'name' => 'ليفينج روم',
                'sort_order' => 1,
                'description' => 'أثاث غرف المعيشة والجلوس',
                'image' => 'images/categories/athath-living-room.svg',
            ],
            'bedrooms' => [
                'name' => 'غرف نوم',
                'sort_order' => 2,
                'description' => 'أسرة وخزائن وغرف نوم كاملة',
                'image' => 'images/categories/athath-bedrooms.svg',
            ],
            'dining-rooms' => [
                'name' => 'غرف سفره',
                'sort_order' => 3,
                'description' => 'سفرة وكراسي وبوفيهات',
                'image' => 'images/categories/athath-dining-rooms.svg',
            ],
            'salons' => [
                'name' => 'صالونات',
                'sort_order' => 4,
                'description' => 'صالونات ومجالس ضيافة',
                'image' => 'images/categories/athath-salons.svg',
            ],
            'outdoor' => [
                'name' => 'اوت دور',
                'sort_order' => 5,
                'description' => 'أثاث خارجي للحدائق والتراس',
                'image' => 'images/categories/athath-outdoor.svg',
            ],
            'libraries' => [
                'name' => 'مكتبات',
                'sort_order' => 6,
                'description' => 'مكتبات ووحدات تخزين للكتب',
                'image' => 'images/categories/athath-libraries.svg',
            ],
        ],
        'ceramics' => [
            'indoor-flooring' => [
                'name' => 'أرضيات داخليه',
                'sort_order' => 1,
                'description' => 'سيراميك وبورسلين للأرضيات الداخلية',
                'image' => 'images/categories/ceramics-indoor-flooring.svg',
            ],
            'walls' => [
                'name' => 'حوائط',
                'sort_order' => 2,
                'description' => 'تشطيبات وجدران سيراميك وبورسلين',
                'image' => 'images/categories/ceramics-walls.svg',
            ],
            'outdoor-flooring' => [
                'name' => 'أرضيات خارجيه',
                'sort_order' => 3,
                'description' => 'أرضيات خارجية مقاومة للعوامل الجوية',
                'image' => 'images/categories/ceramics-outdoor-flooring.svg',
            ],
            'porcelain' => [
                'name' => 'بورسلين',
                'sort_order' => 4,
                'description' => 'بورسلين فاخر للأرضيات والحوائط',
                'image' => 'images/categories/ceramics-porcelain.svg',
            ],
        ],
    ],

    /**
     * Unified subcategories under home-appliances (shared by all appliance scrapers).
     * Keys are canonical category slugs used in the storefront.
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
