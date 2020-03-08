<?php

use App\DataMapper\ClientSettings;
use App\DataMapper\CompanySettings;
use Faker\Generator as Faker;

$factory->define(App\Models\Quote::class, function (Faker $faker) {
    return [
		'status_id' => App\Models\Quote::STATUS_DRAFT,
		'number' => '',
		'discount' => $faker->numberBetween(1,10),
		'is_amount_discount' => $faker->boolean(),
		'tax_name1' => 'GST',
		'tax_rate1' => 10,
		'tax_name2' => 'VAT',
		'tax_rate2' => 17.5,
		'tax_name3' => 'THIRDTAX',
		'tax_rate3' => 5,
		'custom_value1' => $faker->numberBetween(1,4),
		'custom_value2' => $faker->numberBetween(1,4),
		'custom_value3' => $faker->numberBetween(1,4),
		'custom_value4' => $faker->numberBetween(1,4),
		'is_deleted' => false,
		'po_number' => $faker->text(10),
		'date' => $faker->date(),
		'due_date' => $faker->date(),
		'line_items' => false,
    ];
});