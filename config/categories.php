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
            'image' => 'images/categories/accessories.jpg',
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
