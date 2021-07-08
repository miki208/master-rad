<?php

namespace Database\Factories;

use App\Models\RunnerStats;
use Illuminate\Database\Eloquent\Factories\Factory;

class RunnerStatsFactory extends Factory
{
    protected $model = RunnerStats::class;

    public function definition()
    {
        return [
            'runner_id' => 1
        ];
    }
}
