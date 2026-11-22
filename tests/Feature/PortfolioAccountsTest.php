<?php

namespace Tests\Feature;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class PortfolioAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_is_immutable_and_cannot_be_deleted_through_eloquent(): void
    {
        $owner = User::factory()->create();
        $owner->is_portfolio_owner = true;
        $owner->save();

        $this->assertTrue($owner->fresh()->is_portfolio_owner);

        try {
            $owner->delete();
            $this->fail('Owner deletion must not be possible.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('no puede eliminarse', $e->getMessage());
        }

        try {
            $owner->is_portfolio_owner = false;
            $owner->save();
            $this->fail('Owner protection must not be removed.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('No se puede retirar', $e->getMessage());
        }

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'is_portfolio_owner' => true]);
    }

    public function test_regular_accounts_cannot_set_owner_flag_by_mass_assignment(): void
    {
        $user = User::factory()->create();
        $user->fill(['is_portfolio_owner' => true])->save();

        $this->assertFalse($user->fresh()->is_portfolio_owner);
    }

}
