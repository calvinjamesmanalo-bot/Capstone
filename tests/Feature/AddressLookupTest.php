<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_student_can_load_dependent_philippine_address_options(): void
    {
        Cache::flush();
        Http::fake([
            'https://psgc.cloud/api/v2/provinces' => Http::response([['code' => '0402', 'name' => 'Cavite', 'region' => ['code' => '0400000000']]]),
            'https://psgc.cloud/api/v2/provinces/0402/cities-municipalities' => Http::response([['code' => '042106', 'name' => 'City of Dasmariñas']]),
            'https://psgc.cloud/api/v2/cities-municipalities/042106/barangays' => Http::response([['code' => '042106021', 'name' => 'San Jose']]),
        ]);
        $student = Student::create(['student_number' => 'ADDRESS-001', 'name' => 'Address Student', 'official_email' => 'address@example.test']);
        $user = User::factory()->create(['role' => 'student', 'student_number' => $student->student_number, 'email' => $student->official_email, 'email_verified_at' => now()]);

        $this->actingAs($user)->getJson(route('student.address.provinces'))
            ->assertOk()->assertJsonFragment(['name' => 'Cavite'])->assertJsonFragment(['name' => 'Metro Manila (NCR)']);
        $this->getJson(route('student.address.cities', ['province_code' => '0402']))
            ->assertOk()->assertJsonFragment(['name' => 'City of Dasmariñas']);
        $this->getJson(route('student.address.barangays', ['city_code' => '042106']))
            ->assertOk()->assertJsonFragment(['name' => 'San Jose']);
    }

    public function test_address_lookup_rejects_untrusted_codes(): void
    {
        $student = Student::create(['student_number' => 'ADDRESS-002', 'name' => 'Address Student', 'official_email' => 'address2@example.test']);
        $user = User::factory()->create(['role' => 'student', 'student_number' => $student->student_number, 'email' => $student->official_email, 'email_verified_at' => now()]);

        $this->actingAs($user)->getJson(route('student.address.cities', ['province_code' => 'https://example.com']))
            ->assertUnprocessable();
    }
}
