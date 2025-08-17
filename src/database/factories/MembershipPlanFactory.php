<?php

namespace Database\Factories;

use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipPlanFactory extends Factory
{
    protected $model = MembershipPlan::class;

    public function definition(): array
    {
        return [
            'name'          => $this->faker->unique()->randomElement(['Basic', 'Standard', 'Premium']) . ' ' . $this->faker->randomNumber(2),
            'description'   => $this->faker->sentence(),
            'price'         => $this->faker->randomFloat(2, 19, 199),
            'duration_days' => $this->faker->randomElement([30, 90, 180, 365]),
        ];
    }
}
