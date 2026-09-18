<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_investor_side_user_can_see_the_profile_edit_form(): void
    {
        $user = $this->panelUser('investor@example.test');

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('ویرایش اطلاعات')
            ->assertSee('id="profileUserForm"', false)
            ->assertSee(route('profile.user.update'));
    }

    public function test_user_can_update_only_their_own_profile_information(): void
    {
        $user = $this->panelUser('investor@example.test');
        $other = $this->panelUser('other@example.test');

        $this->actingAs($user)
            ->patchJson(route('profile.user.update'), [
                'name' => 'کاربر ویرایش‌شده',
                'father_name' => 'علی',
                'national_id' => '۰۰۱۲۳۴۵۶۷۸',
                'phone' => '۰۹۱۲۱۲۳۴۵۶۷',
                'email' => 'updated@example.test',
                'gender' => 1,
                'postalcode' => '۱۲۳۴۵۶۷۸۹۰',
                'address' => 'نشانی جدید',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'کاربر ویرایش‌شده',
            'national_id' => '0012345678',
            'phone' => '09121234567',
            'email' => 'updated@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $other->id,
            'email' => 'other@example.test',
        ]);
        $this->assertDatabaseHas('user_logs', [
            'user_id' => $user->id,
            'action' => 'profile.user_updated',
            'status' => true,
        ]);
    }

    private function panelUser(string $email): User
    {
        return User::query()->create([
            'name' => 'کاربر سرمایه‌گذار',
            'email' => $email,
            'phone' => fake()->unique()->numerify('09#########'),
            'password' => 'password',
            'level' => 'admin',
            'status' => 4,
            'change_password' => 1,
        ]);
    }
}
