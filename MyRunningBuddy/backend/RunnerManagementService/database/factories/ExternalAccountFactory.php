<?php

namespace Database\Factories;

use App\Models\ExternalAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExternalAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExternalAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'service_name' => 'StravaGatewayService',
            'runner_id' => 1,
            'confirmation_id' => ExternalAccount::CONFIRMATION_ID_REVOKED
        ];
    }
}
