<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_only_sees_their_own_availability(): void
    {
        $this->createUser('client');
        $professionalUser = $this->createUser('professional');
        $professional = Professional::create([
            'user_id' => $professionalUser->id,
            'specialty' => 'Psicología',
            'description' => '',
        ]);
        $otherProfessionalUser = $this->createUser('professional');
        $otherProfessional = Professional::create([
            'user_id' => $otherProfessionalUser->id,
            'specialty' => 'Psicología',
            'description' => '',
        ]);

        $ownAvailability = Availability::create([
            'professional_id' => $professional->id,
            'day_week' => 1,
            'time_start' => '09:00',
            'time_end' => '12:00',
        ]);
        Availability::create([
            'professional_id' => $otherProfessional->id,
            'day_week' => 2,
            'time_start' => '14:00',
            'time_end' => '17:00',
        ]);

        $this->actingAs($professionalUser)
            ->getJson('/api/availability')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $ownAvailability->id);
    }

    public function test_client_can_reserve_in_all_availability_ranges_for_a_day(): void
    {
        $professionalUser = $this->createUser('professional');
        $professional = Professional::create([
            'user_id' => $professionalUser->id,
            'specialty' => 'Psicología',
            'description' => '',
        ]);
        $client = $this->createUser('client');
        $service = Service::create([
            'professional_id' => $professional->id,
            'title' => 'Consulta',
            'description' => 'Consulta inicial',
            'price' => 100,
            'duration' => 30,
        ]);

        foreach ([['09:00', '10:00'], ['14:00', '15:00']] as [$start, $end]) {
            Availability::create([
                'professional_id' => $professional->id,
                'day_week' => 1,
                'time_start' => $start,
                'time_end' => $end,
            ]);
        }

        $this->actingAs($client)
            ->getJson("/api/professionals/{$professional->id}/available-slots?service_id={$service->id}&date=2026-10-05")
            ->assertOk()
            ->assertJsonPath('slots', ['09:00', '09:30', '14:00', '14:30']);
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'lastName' => 'Test',
            'phone' => '123456',
            'role' => $role,
        ]);
    }
}
