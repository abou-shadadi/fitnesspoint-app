<?php

namespace Tests\Feature\Api\V1\Dashboard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class MainDashboardTest extends TestCase
{
    // use RefreshDatabase; // Caution: verify if user wants refresh database. Usually safe in tests but better check environment.
    // Given I don't know the DB setup, I'll avoid refreshing DB if possible or use transaction trait if available.
    // But standardized Laravel tests use RefreshDatabase. I'll assume it's safe for `tests/` but I won't use it to be safe against wiping local dev db if not configured right.
    // Instead I'll just create data and assert.
    
    // Actually, without RefreshDatabase, data persists. I should use DatabaseTransactions if available.
    // use RefreshDatabase;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_dashboard_index_returns_correct_structure()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'active_members' => [
                    'total',
                    'progress_previous_month'
                ],
                'daily_attendance' => [
                    'individual' => ['total', 'progress_previous_day'],
                    'corporate' => ['total', 'progress_previous_day']
                ],
                'expiring_soon' => [
                    'individual' => ['total', 'today'],
                    'corporate' => ['total', 'today']
                ],
                'monthly_revenue' => [
                    'corporate' => ['total_amount', 'progress_last_month'],
                    'individual' => ['total_amount', 'progress_last_month']
                ],
                'weekly_check_ins' => [
                    'individual' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                    'corporate' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
                ],
                'membership_distribution' => [
                    'individual' => ['total', 'monthly', 'quarterly'],
                    'corporate' => ['total', 'monthly', 'quarterly']
                ],
                'revenue_breakdown' => [
                    'individual' => ['january', 'february', 'march', 'april', 'may', 'june', 'jully', 'august', 'september', 'october', 'november', 'december'],
                    'corporate' => ['january', 'february', 'march', 'april', 'may', 'june', 'jully', 'august', 'september', 'october', 'november', 'december'],
                    'individual_and_corporate' => ['january', 'february', 'march', 'april', 'may', 'june', 'jully', 'august', 'september', 'october', 'november', 'december']
                ]
            ]);
    }
}
