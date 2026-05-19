<?php

return [
    'accepted'         => 'يجب قبول حقل :attribute.',
    'between'          => [
        'numeric' => 'يجب أن يكون حقل :attribute بين :min و :max.',
    ],
    'boolean'          => 'يجب أن يكون حقل :attribute صحيحاً أو خاطئاً.',
    'confirmed'        => 'تأكيد حقل :attribute غير متطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'email'            => 'يجب أن يكون حقل :attribute عنوان بريد إلكتروني صالحاً.',
    'exists'           => 'القيمة المحددة لحقل :attribute غير صالحة.',
    'in'               => 'القيمة المحددة لحقل :attribute غير صالحة.',
    'integer'          => 'يجب أن يكون حقل :attribute عدداً صحيحاً.',
    'max'              => [
        'string' => 'يجب ألا يتجاوز حقل :attribute :max حرفاً.',
    ],
    'min'              => [
        'numeric' => 'يجب أن يكون حقل :attribute على الأقل :min.',
        'string'  => 'يجب أن يحتوي حقل :attribute على الأقل على :min أحرف.',
    ],
    'numeric'          => 'يجب أن يكون حقل :attribute رقماً.',
    'regex'            => 'تنسيق حقل :attribute غير صالح.',
    'required'         => 'حقل :attribute إلزامي.',
    'size'             => [
        'string' => 'يجب أن يحتوي حقل :attribute بالضبط على :size أحرف.',
    ],
    'string'           => 'يجب أن يكون حقل :attribute نصاً.',
    'unique'           => 'قيمة حقل :attribute مستخدمة من قبل.',

    'custom' => [
        'phone' => [
            'regex' => 'يجب أن يكون رقم الهاتف بالتنسيق المغربي (0612345678 أو +212612345678).',
        ],
    ],

    'mileage_not_decreasing' => 'لا يمكن أن يتناقص عداد المسافات.',

    'attributes' => [
        'email'                 => 'البريد الإلكتروني',
        'password'              => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'name'                  => 'الاسم',
        'current_password'      => 'كلمة المرور الحالية',
        'locale'                => 'اللغة',
        'first_name'            => 'الاسم الأول',
        'last_name'             => 'اسم العائلة',
        'phone'                 => 'رقم الهاتف',
        'address'               => 'العنوان',
        'notes'                 => 'الملاحظات',
        'customer_id'           => 'العميل',
        'make'                  => 'الصانع',
        'model'                 => 'الموديل',
        'year'                  => 'السنة',
        'license_plate'         => 'رقم اللوحة',
        'vin'                   => 'رقم الهيكل',
        'mileage'               => 'عداد المسافات',
        'fuel_type'             => 'نوع الوقود',
        'color'                 => 'اللون',
        'category'              => 'الفئة',
        'sku'                   => 'رمز المنتج',
        'unit'                  => 'الوحدة',
        'price_buy'             => 'سعر الشراء',
        'price_sell'            => 'سعر البيع',
        'quantity_on_hand'      => 'الكمية المتوفرة',
        'low_stock_threshold'   => 'حد التنبيه للمخزون',
        'quantity'              => 'الكمية',
        'reason'                => 'السبب',
    ],
];
