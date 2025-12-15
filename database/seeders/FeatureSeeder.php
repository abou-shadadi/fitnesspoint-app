<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$features = [
			[
				'name' => 'User management',
				'key' => 'f06302c5-ef44-4aae-8cf5-0c090036fb81',
				'description' => 'Manage users and their roles.',
				'status' => 'active',
			],
			[
				'name' => 'Features management',
				'key' => '07fbd14e-6f5d-4d0e-8e37-f8e670ba0f3f',
				'description' => 'Manage features of the system',
				'status' => 'active',
            ],
            [
                'name' => 'User roles management',
                'key' => 'd5d4f3f7-8f5d-4d0e-8e37-f8e670ba0f3f',
                'description' => 'Manage user roles of the system',
                'status' => 'active',
            ],
            [
                'name' => 'Member management',
                'key' => 'd5d4f3f7-8f5d-4d0e-8e37-f8e670ba0f3f',
                'description' => 'Manage members of the system',
                'status' => 'active',
            ],
            [
                'name' => 'Member import',
                'key' => 'd5d4f3f7-8f5d-4d0e-8e37-f8e670ba0f3f',
                'description' => 'Import members of the system',
                'status' => 'active',
            ],
            [
                'name' => 'Compamany management',
                'key' => 'd5d4f3f7-8f5d-4d0e-8e37-f8e670ba0f3f',
                'description' => 'Manage companies of the system',
                'status' => 'active',
            ]
		];

		foreach ($features as $featureData) {
			$feature = Feature::updateOrCreate(
				['key' => $featureData['key']],
				[
					'name' => $featureData['name'],
					'description' => $featureData['description'],
					'status' => $featureData['status'],
				]
			);
		}
	}
}
