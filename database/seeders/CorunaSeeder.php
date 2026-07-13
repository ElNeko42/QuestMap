<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestStatus;
use App\Enums\ValidationType;
use App\Models\Quest;
use App\Models\Route;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GeoPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CorunaSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'a-coruna'],
            [
                'name' => 'A Coruña',
                'center_point' => new GeoPoint(43.3623, -8.4115),
                'radius_km' => 10,
                'active' => true,
            ],
        );

        $quests = $this->questData();
        $created = [];

        foreach ($quests as $data) {
            $dedup = hash('sha256', $tenant->id.'|'.$data['title']);

            $quest = Quest::updateOrCreate(
                ['dedup_hash' => $dedup],
                [
                    'tenant_id' => $tenant->id,
                    'creator_type' => 'ai',
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'category' => $data['category'],
                    'location' => new GeoPoint($data['lat'], $data['lng']),
                    'geofence_radius_m' => $data['radius'] ?? 50,
                    'validation_type' => $data['validation_type'],
                    'validation_prompt' => $data['validation_prompt'] ?? null,
                    'xp_reward' => $data['xp'],
                    'starts_at' => now()->subDays(7),
                    'expires_at' => now()->addMonths(6),
                    'max_completions' => $data['max_completions'] ?? null,
                    'status' => QuestStatus::Active,
                ],
            );

            $created[$data['key']] = $quest->id;
        }

        $this->seedUsers($tenant);
        $this->seedRoutes($tenant, $created);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function questData(): array
    {
        return [
            [
                'key' => 'torre_hercules', 'title' => 'Torre de Hércules',
                'description' => 'El faro romano en funcionamiento más antiguo del mundo, Patrimonio de la Humanidad. Sube a la colina y captúralo.',
                'category' => 'history', 'lat' => 43.3853, 'lng' => -8.4064,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 200,
                'validation_prompt' => 'Verifica que la foto muestra la Torre de Hércules, un faro de piedra de forma cuadrada sobre una colina junto al mar.',
            ],
            [
                'key' => 'maria_pita', 'title' => 'Plaza de María Pita',
                'description' => 'La plaza mayor de A Coruña, presidida por el Palacio Municipal y la estatua de María Pita.',
                'category' => 'architecture', 'lat' => 43.3703, 'lng' => -8.3959,
                'validation_type' => ValidationType::Checkin, 'xp' => 80,
            ],
            [
                'key' => 'paseo_maritimo', 'title' => 'Paseo Marítimo',
                'description' => 'Uno de los paseos marítimos más largos de Europa. Haz check-in mientras caminas junto al Atlántico.',
                'category' => 'nature', 'lat' => 43.3712, 'lng' => -8.4110,
                'validation_type' => ValidationType::Checkin, 'xp' => 60, 'radius' => 80,
            ],
            [
                'key' => 'monte_san_pedro', 'title' => 'Monte de San Pedro',
                'description' => 'Mirador con cañones históricos y vistas panorámicas de la ciudad y la costa.',
                'category' => 'nature', 'lat' => 43.3577, 'lng' => -8.4335,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 150,
                'validation_prompt' => 'Verifica que la foto muestra una vista panorámica desde una colina, posiblemente con cañones o una cúpula acristalada, sobre la ciudad de A Coruña y el mar.',
            ],
            [
                'key' => 'ciudad_vieja', 'title' => 'Ciudad Vieja',
                'description' => 'Callejea por el casco histórico de A Coruña, entre plazas empedradas y edificios de piedra.',
                'category' => 'history', 'lat' => 43.3720, 'lng' => -8.3930,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 120,
                'validation_prompt' => 'Verifica que la foto muestra una calle o plaza empedrada del casco antiguo con edificios de piedra tradicionales.',
            ],
            [
                'key' => 'playa_riazor', 'title' => 'Playa de Riazor',
                'description' => 'La playa urbana por excelencia de A Coruña. Check-in con la arena bajo los pies.',
                'category' => 'nature', 'lat' => 43.3706, 'lng' => -8.4155,
                'validation_type' => ValidationType::Checkin, 'xp' => 70, 'radius' => 100,
            ],
            [
                'key' => 'playa_orzan', 'title' => 'Playa del Orzán',
                'description' => 'Playa de olas y surf en pleno centro. Captura el mar bravo del Atlántico.',
                'category' => 'nature', 'lat' => 43.3730, 'lng' => -8.4090,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 90,
                'validation_prompt' => 'Verifica que la foto muestra una playa urbana con arena y olas del océano.',
            ],
            [
                'key' => 'domus', 'title' => 'Domus - Casa del Hombre',
                'description' => 'Museo interactivo con forma de vela obra de Arata Isozaki, sobre el Orzán.',
                'category' => 'architecture', 'lat' => 43.3719, 'lng' => -8.4033,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 130,
                'validation_prompt' => 'Verifica que la foto muestra un edificio moderno con forma de vela curva de pizarra oscura junto al mar.',
            ],
            [
                'key' => 'aquarium', 'title' => 'Aquarium Finisterrae',
                'description' => 'Acuario a orillas del Atlántico junto a la Torre de Hércules.',
                'category' => 'nature', 'lat' => 43.3810, 'lng' => -8.4160,
                'validation_type' => ValidationType::Checkin, 'xp' => 100,
            ],
            [
                'key' => 'san_anton', 'title' => 'Castillo de San Antón',
                'description' => 'Fortaleza del siglo XVI sobre una isla, hoy Museo Arqueológico e Histórico.',
                'category' => 'history', 'lat' => 43.3667, 'lng' => -8.3897,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 160,
                'validation_prompt' => 'Verifica que la foto muestra un castillo o fortaleza de piedra rodeado de agua en el puerto.',
            ],
            [
                'key' => 'mendez_nunez', 'title' => 'Jardines de Méndez Núñez',
                'description' => 'Zona verde histórica entre el puerto y la ciudad, ideal para pasear.',
                'category' => 'nature', 'lat' => 43.3690, 'lng' => -8.3970,
                'validation_type' => ValidationType::Checkin, 'xp' => 50,
            ],
            [
                'key' => 'iglesia_santiago', 'title' => 'Iglesia de Santiago',
                'description' => 'La iglesia más antigua de A Coruña, de estilo románico-gótico, en la Ciudad Vieja.',
                'category' => 'history', 'lat' => 43.3716, 'lng' => -8.3937,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 110,
                'validation_prompt' => 'Verifica que la foto muestra una iglesia de piedra de estilo románico o gótico.',
            ],
            [
                'key' => 'colegiata', 'title' => 'Colegiata de Santa María',
                'description' => 'Templo románico del siglo XIII conocido como "la iglesia inclinada".',
                'category' => 'architecture', 'lat' => 43.3710, 'lng' => -8.3928,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 110,
                'validation_prompt' => 'Verifica que la foto muestra la fachada de una colegiata o iglesia románica de piedra.',
            ],
            [
                'key' => 'mercado_san_agustin', 'title' => 'Mercado de San Agustín',
                'description' => 'Mercado de abastos histórico. Descubre un producto típico gallego y anótalo.',
                'category' => 'food', 'lat' => 43.3690, 'lng' => -8.4000,
                'validation_type' => ValidationType::DataInput, 'xp' => 90,
                'validation_prompt' => null,
            ],
            [
                'key' => 'plaza_lugo', 'title' => 'Plaza de Lugo',
                'description' => 'Plaza con mercado de productos frescos y terrazas. Punto gastronómico clave.',
                'category' => 'food', 'lat' => 43.3676, 'lng' => -8.4066,
                'validation_type' => ValidationType::Checkin, 'xp' => 60,
            ],
            [
                'key' => 'calle_franja', 'title' => 'Tapas en la Calle de la Franja',
                'description' => 'Recorre la calle de tapeo tradicional y fotografía una ración gallega.',
                'category' => 'food', 'lat' => 43.3705, 'lng' => -8.3950,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 100,
                'validation_prompt' => 'Verifica que la foto muestra un plato de comida o tapa (por ejemplo pulpo, empanada o pimientos) en un bar o terraza.',
                'max_completions' => 500,
            ],
            [
                'key' => 'murales_monte_alto', 'title' => 'Murales de Monte Alto',
                'description' => 'El barrio de Monte Alto esconde arte urbano en sus fachadas. Encuentra un mural.',
                'category' => 'street_art', 'lat' => 43.3805, 'lng' => -8.4085,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 120,
                'validation_prompt' => 'Verifica que la foto muestra un mural o grafiti artístico pintado en una pared o fachada.',
            ],
            [
                'key' => 'marina', 'title' => 'La Marina y sus galerías',
                'description' => 'Las famosas galerías acristaladas blancas frente al puerto, "la ciudad de cristal".',
                'category' => 'architecture', 'lat' => 43.3688, 'lng' => -8.3930,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 140,
                'validation_prompt' => 'Verifica que la foto muestra la fachada de edificios con galerías acristaladas blancas típicas de A Coruña.',
            ],
            [
                'key' => 'playa_lapas', 'title' => 'Playa de las Lapas',
                'description' => 'Pequeña cala junto al paseo, cerca de la Torre de Hércules.',
                'category' => 'nature', 'lat' => 43.3820, 'lng' => -8.4090,
                'validation_type' => ValidationType::PhotoAi, 'xp' => 80,
                'validation_prompt' => 'Verifica que la foto muestra una pequeña playa o cala rocosa junto al mar.',
            ],
            [
                'key' => 'obelisco', 'title' => 'El Obelisco',
                'description' => 'Monumento modernista en el Cantón Grande, punto de encuentro del centro.',
                'category' => 'history', 'lat' => 43.3688, 'lng' => -8.3987,
                'validation_type' => ValidationType::Checkin, 'xp' => 50,
            ],
        ];
    }

    private function seedUsers(Tenant $tenant): void
    {
        $users = [
            ['name' => 'Alba Vázquez', 'email' => 'alba@questmap.test', 'xp' => 4200],
            ['name' => 'Brais Fernández', 'email' => 'brais@questmap.test', 'xp' => 1800],
            ['name' => 'Carmela Souto', 'email' => 'carmela@questmap.test', 'xp' => 950],
            ['name' => 'Diego Lorenzo', 'email' => 'diego@questmap.test', 'xp' => 300],
            ['name' => 'Uxía Prado', 'email' => 'uxia@questmap.test', 'xp' => 0],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'tenant_id' => $tenant->id,
                    'xp' => $u['xp'],
                    'level' => (int) floor(sqrt($u['xp'] / 100)) + 1,
                ],
            );
        }
    }

    /**
     * @param  array<string, int>  $questIds
     */
    private function seedRoutes(Tenant $tenant, array $questIds): void
    {
        $pick = fn (array $keys) => array_values(array_map(fn ($k) => $questIds[$k], $keys));

        Route::updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Faros y Fortalezas'],
            [
                'theme' => 'history',
                'description' => 'Un recorrido por la historia defensiva y marítima de A Coruña: de la Torre de Hércules al Castillo de San Antón.',
                'quest_ids' => $pick(['torre_hercules', 'aquarium', 'playa_lapas', 'san_anton', 'maria_pita']),
                'estimated_minutes' => 180,
            ],
        );

        Route::updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Sabores y Casco Vello'],
            [
                'theme' => 'food',
                'description' => 'Tapeo y patrimonio en el corazón de la ciudad: mercados, plazas y las callejuelas de la Ciudad Vieja.',
                'quest_ids' => $pick(['plaza_lugo', 'mercado_san_agustin', 'calle_franja', 'ciudad_vieja', 'colegiata', 'iglesia_santiago']),
                'estimated_minutes' => 150,
            ],
        );
    }
}
