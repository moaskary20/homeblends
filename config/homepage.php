<?php

return [
    'announcement' => 'عروض هوم بلند — أجهزة مطبخ بريميوم مع توصيل سريع لكل أنحاء مصر | اشتري الآن وادفع عند الاستلام',

    'top_links' => [
        ['label' => 'برنامج الشركاء', 'url' => '/ar/affiliate-program'],
        ['label' => 'هوم بلند', 'url' => '/ar'],
    ],

    'sub_links' => [
        ['label' => 'عروض فلاش', 'url' => '/ar/products'],
        ['label' => 'باقات التوفير', 'url' => '/ar/bundles'],
        ['label' => 'تواصل معنا', 'url' => '#contact'],
    ],

    'social' => [
        ['label' => 'Facebook', 'url' => 'https://facebook.com', 'icon' => 'facebook'],
        ['label' => 'Instagram', 'url' => 'https://instagram.com', 'icon' => 'instagram'],
        ['label' => 'TikTok', 'url' => 'https://tiktok.com', 'icon' => 'tiktok'],
    ],

    'news_ticker' => [
        'جديد: خلاط هوم بلند برو 1200 واط — خصم 15% لفترة محدودة',
        'شحن مجاني للطلبات فوق 2000 جنيه',
        'باقات مطبخ متكاملة بأسعار لا تُقارن',
        'سجّل في برنامج الشركاء واربح عمولة على كل طلب',
    ],

    'hero_slides' => [
        [
            'title' => 'أثاث',
            'subtitle' => 'أثاث داخلي وخارجي لكل غرف المنزل',
            'cta' => 'اكتشف الآن',
            'url' => '/categories/athath',
            'image' => 'images/hero/athath.jpg',
        ],
        [
            'title' => 'الديكور',
            'subtitle' => 'إكسسوارات وديكورات تكمّل أناقة مساحتك',
            'cta' => 'تسوق الديكور',
            'url' => '/categories/accessories',
            'image' => 'images/hero/accessories.jpg',
        ],
        [
            'title' => 'سيراميك',
            'subtitle' => 'سيراميك وبورسلين للأرضيات والجدران',
            'cta' => 'اكتشف الآن',
            'url' => '/categories/ceramics',
            'image' => 'images/hero/ceramics.jpg',
        ],
    ],

    'partners' => array_map(
        fn (int $i) => [
            'name' => 'شريك '.$i,
            'logo' => 'images/p'.$i.'.png',
        ],
        range(1, 15)
    ),

    'popular_collections' => [
        'section_title' => 'منتجات شائعة',
        'items' => [],
    ],

    'featured_products_limit' => 12,
    'featured_products_per_view' => 4,

    'design_banner' => [
        'is_active' => true,
        'image' => 'images/banner01.png',
        'eyebrow' => 'مهما كانت مساحتك أو ميزانيتك',
        'title' => 'فريق التصميم',
        'subtitle' => 'نحن هنا لمساعدتك، والخدمة مجانية 100%',
        'cta' => 'احجز موعدًا مجانيًا',
        'url' => '/design-team',
    ],

    'catalog_showcase' => [
        'is_active' => true,
        'title' => '',
        'category_id' => null,
        'subcategory_ids' => [],
        'products_limit' => 8,
    ],

    'catalog_showcase_furniture' => [
        'is_active' => true,
        'title' => '',
        'category_id' => 4,
        'subcategory_ids' => [],
        'products_limit' => 8,
    ],

    'promo_banner' => [
        'is_active' => true,
        'image' => 'images/s1.png',
        'cta' => 'تسوق الآن',
        'url' => '/products',
    ],

    'customer_reviews' => [
        'is_active' => true,
        'section_title' => 'آراء العملاء',
        'auto_limit' => 12,
        'items' => [
            [
                'customer_name' => 'Omneya H',
                'rating' => 5,
                'comment' => 'لطيف - جيد',
                'is_verified' => true,
                'image' => 'images/customer-reviews/review-1.jpg',
            ],
            [
                'customer_name' => 'Dina F',
                'rating' => 5,
                'comment' => 'أحببته، إنه مريح للغاية',
                'is_verified' => true,
                'image' => 'images/customer-reviews/review-2.jpg',
            ],
            [
                'customer_name' => 'Naila A',
                'rating' => 5,
                'comment' => 'تجربة توصيل ممتازة، قاعات حفلات مريحة للغاية بالإضافة إلى جودة المنتج',
                'is_verified' => true,
                'image' => 'images/customer-reviews/review-3.webp',
            ],
            [
                'customer_name' => 'Olga V',
                'rating' => 5,
                'comment' => 'جميلة جداً! الجودة رائعة واللون مطابق تماماً لما هو معروض',
                'is_verified' => false,
                'image' => 'images/customer-reviews/review-4.jpg',
            ],
            [
                'customer_name' => 'Sara M',
                'rating' => 5,
                'comment' => 'منتج رائع وأنصح به بشدة',
                'is_verified' => true,
                'image' => 'images/customer-reviews/review-5.jpg',
            ],
            [
                'customer_name' => 'Hana K',
                'rating' => 5,
                'comment' => 'تجربة شراء ممتازة من البداية للنهاية',
                'is_verified' => true,
                'image' => 'images/customer-reviews/review-6.jpg',
            ],
        ],
        'samples' => [
            ['customer_name' => 'Omneya H', 'rating' => 5, 'comment' => 'لطيف - جيد', 'is_verified' => true],
            ['customer_name' => 'Dina F', 'rating' => 5, 'comment' => 'أحببته، إنه مريح للغاية', 'is_verified' => true],
            ['customer_name' => 'Naila A', 'rating' => 5, 'comment' => 'تجربة توصيل ممتازة، قاعات حفلات مريحة للغاية بالإضافة إلى جودة المنتج', 'is_verified' => true],
            ['customer_name' => 'Olga V', 'rating' => 5, 'comment' => 'جميلة جداً! الجودة رائعة واللون مطابق تماماً لما هو معروض', 'is_verified' => false],
            ['customer_name' => 'Sara M', 'rating' => 5, 'comment' => 'منتج رائع وأنصح به بشدة', 'is_verified' => true],
            ['customer_name' => 'Hana K', 'rating' => 5, 'comment' => 'تجربة شراء ممتازة من البداية للنهاية', 'is_verified' => true],
        ],
    ],

    'comfort_spotlight' => [
        'is_active' => true,
        'eyebrow' => 'صمم سرير أحلامك',
        'title' => 'مستلزمات الراحة',
        'description' => 'طبقات ناعمة لراحة يومية ونوم أفضل.',
        'cta' => 'تسوق الكل',
        'url' => '/products',
        'image' => '',
        'hero_product_id' => 6,
        'product_ids' => [6, 4, 5, 3],
        'links' => [
            ['name' => 'الملايات', 'url' => '', 'category_id' => null],
            ['name' => 'حشوات', 'url' => '', 'category_id' => null],
            ['name' => 'المراتب', 'url' => '', 'category_id' => null],
            ['name' => 'ألحفة', 'url' => '', 'category_id' => null],
        ],
    ],

    'contact_strip' => [
        'is_active' => true,
        'items' => [
            [
                'icon' => 'location',
                'title' => 'العناوين',
                'text' => 'اعرف طريقك إلى فروعنا',
                'url' => '#contact',
            ],
            [
                'icon' => 'email',
                'title' => 'تواصل معنا',
                'text' => 'support@homeblendstore.com',
                'url' => 'mailto:support@homeblendstore.com',
            ],
            [
                'icon' => 'phone',
                'title' => 'اتصل بنا',
                'text' => 'الخط الساخن: 17453',
                'url' => 'tel:17453',
            ],
            [
                'icon' => 'chat',
                'title' => 'خدمة العملاء',
                'text' => 'الأحد - السبت: 10 صباحاً - 7 مساءً',
                'url' => '',
            ],
        ],
    ],
];
