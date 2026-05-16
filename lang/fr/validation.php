<?php

// Extend this file as new validation rules are introduced — only a subset is currently covered.
return [
    'accepted'         => 'Le champ :attribute doit être accepté.',
    'between'          => [
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
    ],
    'boolean'          => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed'        => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'email'            => 'Le champ :attribute doit être une adresse e-mail valide.',
    'exists'           => 'La valeur sélectionnée pour :attribute est invalide.',
    'in'               => 'La valeur sélectionnée pour :attribute est invalide.',
    'integer'          => 'Le champ :attribute doit être un nombre entier.',
    'max'              => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'min'              => [
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'string'  => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'numeric'          => 'Le champ :attribute doit être un nombre.',
    'regex'            => 'Le format du champ :attribute est invalide.',
    'required'         => 'Le champ :attribute est obligatoire.',
    'size'             => [
        'string' => 'Le champ :attribute doit contenir exactement :size caractères.',
    ],
    'string'           => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique'           => 'La valeur du champ :attribute est déjà utilisée.',

    'custom' => [
        'phone' => [
            'regex' => 'Le téléphone doit être au format marocain (0612345678 ou +212612345678).',
        ],
    ],

    'mileage_not_decreasing' => 'Le kilométrage ne peut pas diminuer.',

    'attributes' => [
        // Auth
        'email'                 => 'adresse e-mail',
        'password'              => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'name'                  => 'nom',
        'current_password'      => 'mot de passe actuel',
        // Customer
        'first_name'            => 'prénom',
        'last_name'             => 'nom de famille',
        'phone'                 => 'téléphone',
        'address'               => 'adresse',
        'notes'                 => 'notes',
        'notify_email'          => 'notification par e-mail',
        'notify_whatsapp'       => 'notification par WhatsApp',
        // Vehicle
        'customer_id'           => 'client',
        'make'                  => 'marque',
        'model'                 => 'modèle',
        'year'                  => 'année',
        'license_plate'         => 'plaque d\'immatriculation',
        'vin'                   => 'numéro de châssis',
        'mileage'               => 'kilométrage',
        'fuel_type'             => 'type de carburant',
        'color'                 => 'couleur',
        // Service Record
        'mileage_at_service'    => 'kilométrage au moment du service',
        'serviced_at'           => 'date du service',
        'notes'                 => 'notes',
        'service_type_ids'      => 'types de service',
        // Product
        'category'              => 'catégorie',
        'sku'                   => 'référence',
        'unit'                  => 'unité',
        'price_buy'             => "prix d'achat",
        'price_sell'            => 'prix de vente',
        'quantity_on_hand'      => 'quantité en stock',
        'low_stock_threshold'   => "seuil d'alerte de stock",
    ],
];
