<?php

return [

    // Locales
    'locale_fr' => 'Français',
    'locale_ar' => 'العربية',
    'language'  => 'اللغة',

    // Layout / nav
    'open_menu'        => 'فتح القائمة',
    'close_menu'       => 'إغلاق القائمة',
    'profile'          => 'الملف الشخصي',
    'logout'           => 'تسجيل الخروج',
    'nav_home'         => 'لوحة التحكم',
    'nav_customers'    => 'العملاء',
    'nav_vehicles'     => 'المركبات',
    'nav_invoices'     => 'الفواتير والعروض',
    'nav_products'     => 'المنتجات',
    'nav_services'     => 'الخدمات',
    'nav_appointments' => 'المواعيد',
    'nav_today'        => 'اليوم',
    'nav_workshop'     => 'الورشة',
    'nav_fleet_notes'  => 'ملاحظات الأسطول',
    'nav_reminders'    => 'التذكيرات',
    'nav_users'        => 'المستخدمون',
    'nav_settings'     => 'الإعدادات',
    'nav_more'         => 'المزيد',

    // Notifications
    'notifications'    => 'الإشعارات',
    'mark_all_read'    => 'وضع الكل كمقروء',
    'no_notifications' => 'لا توجد إشعارات',

    // Auth — ConfirmPassword
    'confirm_password'      => 'تأكيد كلمة المرور',
    'confirm_password_desc' => 'هذه منطقة آمنة. يرجى تأكيد كلمة مرورك قبل المتابعة.',
    'confirm'               => 'تأكيد',

    // Auth — Register
    'register'           => 'إنشاء حساب',
    'name'               => 'الاسم',
    'email'              => 'البريد الإلكتروني',
    'password'           => 'كلمة المرور',
    'register_submit'    => 'إنشاء حساب',
    'already_registered' => 'لديك حساب بالفعل؟',

    // Auth — VerifyEmail
    'verify_email'        => 'التحقق من البريد الإلكتروني',
    'verify_email_desc'   => "شكراً لتسجيلك! قبل البدء، يرجى التحقق من بريدك الإلكتروني بالنقر على الرابط الذي أرسلناه إليك. إذا لم تتلقَّ البريد الإلكتروني، يسعدنا إرسال آخر.",
    'verify_email_sent'   => 'تم إرسال رابط تحقق جديد إلى عنوان بريدك الإلكتروني.',
    'resend_verification' => 'إعادة إرسال البريد الإلكتروني للتحقق',

    // Profile
    'profile_title'       => 'الملف الشخصي',
    'language_preference' => 'لغة الواجهة',
    'language_saved'      => 'تم تحديث اللغة.',

    'profile_info_title' => 'معلومات الملف الشخصي',
    'profile_info_desc'  => 'قم بتحديث معلومات ملفك الشخصي وعنوان بريدك الإلكتروني.',
    'profile_info_saved' => 'تم الحفظ.',
    'email_unverified'   => 'لم يتم التحقق من عنوان بريدك الإلكتروني.',
    'resend_email_link'  => 'انقر هنا لإعادة إرسال بريد التحقق.',

    'update_password_title' => 'تغيير كلمة المرور',
    'update_password_desc'  => 'تأكد من أن حسابك يستخدم كلمة مرور طويلة وعشوائية للبقاء آمناً.',
    'current_password'      => 'كلمة المرور الحالية',
    'new_password'          => 'كلمة المرور الجديدة',

    'delete_account_title'         => 'حذف الحساب',
    'delete_account_desc'          => 'بمجرد حذف حسابك، ستُحذف جميع موارده وبياناته نهائياً. قبل حذف حسابك، يرجى تنزيل أي بيانات تريد الاحتفاظ بها.',
    'delete_account_confirm_title' => 'هل أنت متأكد من رغبتك في حذف حسابك؟',
    'delete_account_confirm_desc'  => 'بمجرد حذف حسابك، ستُحذف جميع موارده وبياناته نهائياً. يرجى إدخال كلمة مرورك لتأكيد الحذف.',

    // Common buttons
    'save'   => 'حفظ',
    'cancel' => 'إلغاء',
    'delete' => 'حذف الحساب',
    'edit'   => 'تعديل',
    'back'   => 'رجوع →',
    'search' => 'بحث',

    // Invoices
    'invoice_product_unavailable' => 'تم حذف المنتج « :name » من الكتالوج؛ احذف هذا السطر قبل الفوترة.',

    // Stock units next to a quantity, keyed by ProductUnit value.
    // `one` for |quantity| <= 1, `other` above (see ProductUnit::quantityLabel()).
    'units' => [
        'unite'      => ['one' => 'وحدة', 'other' => 'وحدات'],
        'litre'      => ['one' => 'ل', 'other' => 'ل'],
        'millilitre' => ['one' => 'مل', 'other' => 'مل'],
        'kg'         => ['one' => 'كغ', 'other' => 'كغ'],
        'bidon'      => ['one' => 'بيدون', 'other' => 'بيدونات'],
        'flacon'     => ['one' => 'قارورة', 'other' => 'قوارير'],
        'boite'      => ['one' => 'علبة', 'other' => 'علب'],
        'paire'      => ['one' => 'زوج', 'other' => 'أزواج'],
        'jeu'        => ['one' => 'طقم', 'other' => 'أطقم'],
        'kit'        => ['one' => 'عدة', 'other' => 'عدد'],
    ],
];
