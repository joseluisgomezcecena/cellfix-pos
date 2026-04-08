<?php

return [
    'name' => 'PromoCode',

    /**
     * Category names for promo discounts
     * These should match your product categories in the database
     */
    'categories' => [
        'equipos' => 'Equipos',
        'pantallas' => 'Pantallas',
        'accesorios' => 'Accesorios',
        'desbloqueos' => 'Desbloqueos',
    ],

    /**
     * Category mapping - maps product category names to promo categories
     * This allows flexible matching between database categories and promo categories
     */
    'category_mapping' => [
        'equipos' => [
            'EQUIPOS', 'Equipos', 'equipos',
            'CELULARES', 'Celulares', 'celulares',
            'SMARTPHONES', 'Smartphones', 'smartphones',
            'Equipo',
        ],
        'pantallas' => [
            'PANTALLAS', 'Pantallas', 'pantallas',
            'DISPLAYS', 'Displays', 'displays',
        ],
        'accesorios' => [
            'ACCESORIOS', 'Accesorios', 'accesorios',
            'ACCESORIOS NOVEDOSOS', 'Accesorios Novedosos', 'accesorios novedosos',
            'PROTECTORES', 'Protectores', 'protectores',
            'PROTECTOR', 'Protector', 'protector',
            'CARGADORES', 'Cargadores', 'cargadores',
            'BOCINAS', 'Bocinas', 'bocinas',
            'AUDIFONOS', 'Audifonos', 'audifonos',
            'CABLES', 'Cables', 'cables',
            // Screen Protectors
            'VT', 'Vt', 'vt',
            // Phone Cases
            'IPHONE CASE', 'iPhone Case', 'iphone case',
            'SAMSUNG CASE', 'Samsung Case', 'samsung case',
            'MOTOROLA CASE', 'Motorola Case', 'motorola case',
            'HUAWEI CASE', 'Huawei Case', 'huawei case',
            'REDMI CASE', 'Redmi Case', 'redmi case',
            'OPPO CASE', 'Oppo Case', 'oppo case',
            'HONOR CASE', 'Honor Case', 'honor case',
            'REALME CASE', 'Realme Case', 'realme case',
            'ZTE CASE', 'Zte Case', 'zte case',
            // Case Types
            'Alfa Case', 'alfa case',
            'Anillo Case', 'anillo case',
            'Apple Watch Case VT', 'apple watch case vt',
            'Cartera Clear Case', 'cartera clear case',
            'Cartera Piel Case', 'cartera piel case',
            'Cartera Plastico Case', 'cartera plastico case',
            'Clear Case', 'clear case',
            'Clear Magsafe Case', 'clear magsafe case',
            'Color Magsafe Case', 'color magsafe case',
            'Drop Yuval Case', 'drop yuval case',
            'Gamusa Case', 'gamusa case',
            'Hard Case', 'hard case',
            'Hard Case Magsafe', 'hard case magsafe',
            'Lens Case', 'lens case',
            'Lens Case Magsafe', 'lens case magsafe',
            'Magnetic Lens Circulo Case', 'magnetic lens circulo case',
            'Magnetic Lens Liso Case', 'magnetic lens liso case',
            'Magnetic Space Case', 'magnetic space case',
            'Mate Magsafe Case', 'mate magsafe case',
            'Metal Case', 'metal case',
            'Neon Case', 'neon case',
            'Neon Magsafe Case', 'neon magsafe case',
            'Pop Socket Case', 'pop socket case',
            'Robot Case', 'robot case',
            'Silicone Case', 'silicone case',
            'Silicone Case Magsafe Premium', 'silicone case magsafe premium',
            'Tarjetero PLastico Case', 'tarjetero plastico case',
            'Tornasol Magsafe', 'tornasol magsafe',
            'Waterproof', 'waterproof',
            // Apple Watch & Accessories
            'APPLE WATCH ACCS', 'Apple Watch Accs', 'apple watch accs',
            'Apple Watch', 'apple watch',
            'PREMIUM Alpine Loop Band', 'premium alpine loop band',
            'PREMIUM Braided Solo Loop Band', 'premium braided solo loop band',
            'PREMIUM Milanesse Loop Band', 'premium milanesse loop band',
            'PREMIUM Nike Sport Band', 'premium nike sport band',
            'PREMIUM Sport Band', 'premium sport band',
            'PREMIUM Sport Loop Band', 'premium sport loop band',
            // iPad & Tab Accessories
            'IPAD Y TAB ACCESORIOS', 'iPad y Tab Accesorios', 'ipad y tab accesorios',
            'iPad', 'ipad',
            'Tab', 'tab',
            'Tabs', 'tabs',
            // Other Accessories
            'Baterias Portatiles', 'baterias portatiles',
            'Power Bank', 'power bank',
            'Soporte carros', 'soporte carros',
            'Cargadores Carro', 'cargadores carro',
            'Lens Camara', 'lens camara',
            'Memorias', 'memorias',
            'Pop Socket Case', 'pop socket case',
            'Extensibles Completo', 'extensibles completo',
            'Extensibles Deportivos', 'extensibles deportivos',
            'Extensibles Lisos', 'extensibles lisos',
            // Specific protector categories
            'HUAWEI PROTECTORES', 'Huawei Protectores', 'huawei protectores',
            'MOTOROLA PROTECTORES', 'Motorola Protectores', 'motorola protectores',
            'OPPO RENO PROTECTORES', 'Oppo Reno Protectores', 'oppo reno protectores',
            'SAMSUNG PROTECTORES', 'Samsung Protectores', 'samsung protectores',
            'XIAOMI REDMI PROTECTORES', 'Xiaomi Redmi Protectores', 'xiaomi redmi protectores',
            'ZTE PROTECTORES', 'Zte Protectores', 'zte protectores',
            'Hidrogel', 'hidrogel',
            'Blindajes', 'blindajes',
            // Repair Parts / Refacciones
            'REFACCIONES', 'Refacciones', 'refacciones',
            'REFACCIONES IPHONE', 'Refacciones iPhone', 'refacciones iphone',
            'REFACCIONES BATERIAS', 'Refacciones Baterias', 'refacciones baterias',
            // Other
            'RECARGAS TELEFONICAS', 'Recargas Telefonicas', 'recargas telefonicas',
            'LG', 'Lg', 'lg',
        ],
        'desbloqueos' => [
            'DESBLOQUEOS', 'Desbloqueos', 'desbloqueos',
            'SERVICIOS', 'Servicios', 'servicios',
            'SERVICIO TECNICO', 'Servicio Tecnico', 'servicio tecnico',
            'PEDIDOS Y SERVICIO TECNICO', 'Pedidos y Servicio Tecnico', 'pedidos y servicio tecnico',
            'Rsim', 'rsim',
        ],
    ],
];
