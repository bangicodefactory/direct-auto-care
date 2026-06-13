<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected static array $firstNames = [
        'Youssef', 'Omar', 'Mohammed', 'Ahmed', 'Karim',
        'Hassan', 'Rachid', 'Hamza', 'Soufiane', 'Ayoub',
        'Sara', 'Fatima', 'Khadija', 'Nadia', 'Imane',
        'Sanaa', 'Layla', 'Zineb', 'Hanane', 'Meryem',
    ];

    protected static array $lastNames = [
        'El Amrani', 'Bennani', 'Haddad', 'Bouhali', 'Tazi',
        'Alaoui', 'Chraibi', 'Fassi', 'Berrada', 'Squalli',
        'El Idrissi', 'Benali', 'Rahmouni', 'Benhaddou', 'Lahlou',
        'Kettani', 'El Khatib', 'Bouazza', 'Senhaji', 'Guessous',
    ];

    public function definition(): array
    {
        $firstName = fake()->randomElement(self::$firstNames);
        $lastName = fake()->randomElement(self::$lastNames);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'type' => 'individual',
            'ice' => null,
            'phone' => '+212' . fake()->unique()->numerify('6########'),
            'email' => fake()->optional(0.8)->safeEmail(),
            'address' => fake()->optional(0.6)->randomElement([
                'Rue Mohammed V, Casablanca',
                'Avenue Hassan II, Rabat',
                'Boulevard Zerktouni, Casablanca',
                'Rue Ibn Sina, Fès',
                'Avenue Mohammed VI, Marrakech',
                'Rue Allal Ben Abdellah, Rabat',
                'Boulevard Anfa, Casablanca',
                'Rue de la Liberté, Tanger',
                'Avenue des FAR, Agadir',
                'Rue Oued Fès, Salé',
                'Boulevard Mohammed V, Meknès',
                'Rue Bab Doukkala, Marrakech',
                'Avenue Bir Anzarane, Laâyoune',
                'Rue Tariq Ibn Ziad, Oujda',
                'Boulevard Abdelkrim Al Khattabi, Tétouan',
            ]),
            'notes' => fake()->optional(0.3)->sentence(),
            'notify_email' => fake()->boolean(30),
            'notify_whatsapp' => fake()->boolean(40),
        ];
    }

    /**
     * A B2B (société) customer with a 15-digit ICE.
     */
    public function company(): static
    {
        return $this->state(fn (): array => [
            'type' => 'company',
            'ice' => (string) fake()->unique()->numerify('###############'),
        ]);
    }
}
