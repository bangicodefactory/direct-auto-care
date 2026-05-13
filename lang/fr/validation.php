<?php

// Extend this file as new validation rules are introduced — only a subset is currently covered.
return [
    'accepted'             => 'Le champ :attribute doit être accepté.',
    'email'                => 'Le champ :attribute doit être une adresse e-mail valide.',
    'confirmed'            => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password'     => 'Le mot de passe est incorrect.',
    'max'                  => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'min'                  => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'required'             => 'Le champ :attribute est obligatoire.',
    'string'               => 'Le champ :attribute doit être une chaîne de caractères.',
    'boolean'              => 'Le champ :attribute doit être vrai ou faux.',
    'in'                   => 'La valeur sélectionnée pour :attribute est invalide.',
    'unique'               => 'La valeur du champ :attribute est déjà utilisée.',

    'attributes' => [
        'email'                 => 'adresse e-mail',
        'password'              => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'name'                  => 'nom',
        'current_password'      => 'mot de passe actuel',
    ],
];
