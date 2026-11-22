<?php

namespace Tests\Feature;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InteractivePortfolioAccessTest extends TestCase
{
    use RefreshDatabase;

    private function setupDemo(?string $role = 'supervisor', bool $owner = false): User
    {
        $plant = Plant::create(['code' => 'DEMO-PUBLIC', 'name' => 'Planta ficticia']);
        $user = User::factory()->create();
        $user->is_portfolio_demo_admin = true;
        $user->is_portfolio_owner = $owner;
        $user->save();

        if ($role !== null) {
            DB::table('plant_user')->insert([
                'plant_id' => $plant->id,
                'user_id' => $user->id,
                'role' => $role,
            ]);
        }

        return $user;
    }

    public function test_quick_access_requires_a_valid_demo_user_and_correct_scoped_membership(): void
    {
        $this->post('/demo/entrar')->assertStatus(503);
        $user = $this->setupDemo();

        $this->post('/demo/entrar')->assertRedirect(route('portfolio.guide'));
        $this->assertAuthenticatedAs($user);
        $this->get('/guia')->assertOk()->assertSee('Programar auditoría');
    }

    public function test_demo_login_does_not_accept_cross_plant_admin(): void
    {
        $user = $this->setupDemo();
        $other = Plant::create(['code' => 'OTHER', 'name' => 'Otra planta']);
        DB::table('plant_user')->insert([
            'plant_id' => $other->id,
            'user_id' => $user->id,
            'role' => 'supervisor',
        ]);

        $this->post('/demo/entrar')->assertStatus(503);
        $this->assertGuest();
    }

    public function test_owner_is_never_accepted_as_public_demo_account(): void
    {
        $this->setupDemo('supervisor', true);
        $this->post('/demo/entrar')->assertStatus(503);
        $this->assertGuest();
    }

    public function test_demo_login_rejects_missing_or_low_privilege_membership(): void
    {
        $this->setupDemo(null);
        $this->post('/demo/entrar')->assertStatus(503);
        $this->assertGuest();
    }

    public function test_authenticated_guide_is_not_public(): void
    {
        $this->get('/guia')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Entrar como administrador demo');
    }
}
