<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleNote>
 */
class VehicleNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'user_id'    => User::factory(),
            'body'       => fake()->randomElement([
                'Pneus avant à surveiller, usure irrégulière.',
                'Le chauffeur signale un bruit au freinage.',
                'Vidange prévue au prochain passage.',
                'Rayure portière arrière gauche constatée à la réception.',
                'Voyant moteur allumé, diagnostic à faire.',
            ]),
        ];
    }
}
