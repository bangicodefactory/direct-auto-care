<?php

// Extend this file as new validation rules are introduced — only a subset is currently covered.
return [
    'accepted'         => 'Le champ :attribute doit être accepté.',
    'boolean'          => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed'        => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'email'            => 'Le champ :attribute doit être une adresse e-mail valide.',
    'in'               => 'La valeur sélectionnée pour :attribute est invalide.',
    'max'              => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'min'              => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'regex'            => 'Le format du champ :attribute est invalide.',
    'required'         => 'Le champ :attribute est obligatoire.',
    'string'           => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique'           => 'La valeur du champ :attribute est déjà utilisée.',

    'custom' => [
        'phone' => [
            'regex' => 'Le téléphone doit être au format marocain (0612345678 ou +212612345678).',
        ],
    ],

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
    ],
];
