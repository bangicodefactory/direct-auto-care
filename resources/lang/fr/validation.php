<?php

return [

    'required' => 'Ce champ est obligatoire.',
    'email' => "L'adresse email n'est pas valide.",
    'min' => [
        'string' => 'Ce champ doit contenir au moins :min caractères.',
    ],
    'max' => [
        'string' => 'Ce champ ne doit pas dépasser :max caractères.',
    ],
    'numeric' => 'Ce champ doit être un nombre.',
    'integer' => 'Ce champ doit être un entier.',
    'confirmed' => 'La confirmation ne correspond pas.',
    'accepted' => 'Vous devez accepter ce champ.',
    'boolean' => 'Ce champ doit être vrai ou faux.',

    // File uploads
    'image' => 'Le fichier doit être une image.',
    'mimes' => 'Format de fichier invalide.',
    'max_file' => 'Le fichier est trop volumineux.',

    
    'custom' => [
        'license_plate' => [
            'unique' => "La valeur du champ plaque d'immatriculation est déjà utilisée.",
        ],
        'mileage' => [
            'mileage_not_decreasing' => 'Le kilométrage ne peut pas diminuer.',
        ],
    ],

    'unique' => 'Cette valeur est déjà utilisée.',

];