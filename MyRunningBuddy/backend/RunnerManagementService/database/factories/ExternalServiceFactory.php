<?php

namespace Database\Factories;

use App\Models\ExternalService;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExternalServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExternalService::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'service_name' => 'StravaGatewayService',
            'human_friendly_name' => 'Strava'
        ];
    }
}
