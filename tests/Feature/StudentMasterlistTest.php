<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentMasterlistImport;
use App\Models\Setting;
use App\Models\User;
use App\Support\StudentMasterlistPdfParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentMasterlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_masterlist_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->get(route('account-creation.index'))->assertOk()->assertSee('Upload student PDF');
        $this->actingAs($registrar)->get(route('account-creation.index'))->assertForbidden();
        $this->actingAs($student)->get(route('account-creation.index'))->assertForbidden();
    }

    public function test_admin_can_disable_dark_mode_for_the_whole_portal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('settings.update'), [
            'dark_mode_enabled' => '0',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'dark_mode_enabled', 'value' => '0']);
        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()->assertSee('Dark mode')->assertDontSee('data-theme-toggle', false);
    }

    public function test_admin_can_import_masterlist_and_make_student_eligible(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->mock(StudentMasterlistPdfParser::class, function ($mock) {
            $mock->shouldReceive('parse')->once()->andReturn([
                'school_year' => '2024-2025',
                'rows' => [[
                    'student_number' => '20240001',
                    'lrn' => '123456789012',
                    'name' => 'Dela Cruz, Juan Santos',
                    'gender' => 'Male',
                    'campus' => 'Imus',
                    'grade_level' => 'Grade 7',
                    'section' => 'Erbium',
                ]],
            ]);
        });

        $response = $this->actingAs($admin)->post(route('account-creation.store'), [
            'masterlist_pdf' => UploadedFile::fake()->create('Student List.pdf', 100, 'application/pdf'),
        ]);
        $response->assertRedirect(route('account-creation.index'))->assertSessionHas('success')
            ->assertSessionHas('masterlist_account_results', fn ($results) => $results['created'] === 1 && $results['skipped'] === 0);
        $credential = session('masterlist_account_results')['credentials'][0];

        $this->assertDatabaseHas('students', [
            'student_number' => '20240001',
            'lrn' => '123456789012',
            'masterlist_active' => true,
            'masterlist_grade_level' => 'Grade 7',
            'masterlist_section' => 'Erbium',
        ]);
        $this->assertSame(1, StudentMasterlistImport::firstOrFail()->imported_rows);
        $this->assertTrue(Student::findOrFail('20240001')->masterlist_active);
        $account = User::where('student_number', '20240001')->firstOrFail();
        $this->assertTrue($account->must_change_password);
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Za-km-z2-9]{4}-[A-HJ-NP-Za-km-z2-9]{4}-[A-HJ-NP-Za-km-z2-9]{4}$/', $credential['temporary_password']);
        $this->assertStringNotContainsString('20240001', $credential['temporary_password']);
        $this->assertTrue(Hash::check($credential['temporary_password'], $account->password));
        $this->assertSame('argon2id', password_get_info($account->password)['algoName']);
        $this->assertSame($account->email, Student::findOrFail('20240001')->official_email);
    }

    public function test_yearly_import_reuses_an_existing_student_without_duplication(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        Student::create([
            'student_number' => '20240001',
            'lrn' => '123456789012',
            'name' => 'Original Student Name',
            'official_email' => 'student@gmail.com',
        ]);
        $this->mock(StudentMasterlistPdfParser::class, function ($mock) {
            $mock->shouldReceive('parse')->once()->andReturn([
                'school_year' => '2025-2026',
                'rows' => [[
                    'student_number' => '20240001',
                    'lrn' => '123456789012',
                    'name' => 'Changed Spreadsheet Name',
                    'gender' => 'Male',
                    'campus' => 'Dasmarinas',
                    'grade_level' => 'Grade 8',
                    'section' => 'Gold',
                ]],
            ]);
        });

        $this->actingAs($admin)->post(route('account-creation.store'), [
            'masterlist_pdf' => UploadedFile::fake()->create('Next Year.pdf', 101, 'application/pdf'),
        ])->assertRedirect(route('account-creation.index'));

        $this->assertSame(1, Student::where('student_number', '20240001')->count());
        $student = Student::findOrFail('20240001');
        $this->assertSame('Original Student Name', $student->name);
        $this->assertSame('20240001@student.fiatlux.local', $student->official_email);
        $this->assertSame('2025-2026', $student->masterlist_school_year);
        $this->assertSame('Grade 8', $student->masterlist_grade_level);
        $this->assertSame(0, StudentMasterlistImport::firstOrFail()->new_rows);
        $this->assertSame(1, StudentMasterlistImport::firstOrFail()->reused_rows);
    }

    public function test_existing_account_is_skipped_without_resetting_its_password(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create(['student_number' => '20249999', 'name' => 'Existing, Student', 'official_email' => 'existing@gmail.com']);
        $account = User::factory()->create(['role' => 'student', 'student_number' => $student->student_number, 'email' => 'existing@gmail.com', 'password' => Hash::make('ExistingPass!2026')]);
        $originalHash = $account->password;
        $this->mock(StudentMasterlistPdfParser::class, fn ($mock) => $mock->shouldReceive('parse')->once()->andReturn([
            'school_year' => '2025-2026', 'rows' => [[
                'student_number' => '20249999', 'lrn' => '123456789099', 'name' => 'Existing, Student', 'gender' => 'Male',
                'campus' => 'Imus', 'grade_level' => 'Grade 8', 'section' => 'Gold',
            ]],
        ]));

        $this->actingAs($admin)->post(route('account-creation.store'), [
            'masterlist_pdf' => UploadedFile::fake()->create('Existing.pdf', 100, 'application/pdf'),
        ])->assertSessionHas('masterlist_account_results', fn ($results) => $results['created'] === 0 && $results['skipped'] === 1);

        $this->assertSame($originalHash, $account->fresh()->password);
    }

    public function test_student_must_replace_generated_password_before_using_the_portal(): void
    {
        $student = Student::create(['student_number' => 'TEMP-100', 'name' => 'Student,,Temporary,', 'official_email' => 'temp@student.fiatlux.local', 'masterlist_active' => true]);
        $user = User::factory()->create(['role' => 'student', 'student_number' => $student->student_number, 'email' => $student->official_email, 'must_change_password' => true, 'password' => Hash::make('TS100')]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('temporary-password.edit'));
        $this->actingAs($user)->post(route('temporary-password.update'), ['password' => 'New Secure!2026', 'password_confirmation' => 'New Secure!2026'])
            ->assertRedirect(route('dashboard'));
        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('New Secure!2026', $user->fresh()->password));
    }

    public function test_admin_can_issue_a_random_temporary_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = Student::create(['student_number' => 'TEMP-200', 'name' => 'Student, Reset', 'official_email' => 'temp-200@student.fiatlux.local', 'masterlist_active' => true]);
        $account = User::factory()->create(['role' => 'student', 'student_number' => $student->student_number, 'email' => $student->official_email, 'must_change_password' => false, 'password' => Hash::make('Old Secure!2026')]);

        $this->actingAs($registrar)->post(route('account-creation.reissue-password', $student))->assertForbidden();
        $response = $this->actingAs($admin)->post(route('account-creation.reissue-password', $student));
        $response->assertRedirect()->assertSessionHas('reissued_student_credential');
        $temporary = session('reissued_student_credential')['temporary_password'];

        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Za-km-z2-9]{4}-[A-HJ-NP-Za-km-z2-9]{4}-[A-HJ-NP-Za-km-z2-9]{4}$/', $temporary);
        $this->assertStringNotContainsString('TEMP-200', $temporary);
        $this->assertTrue(Hash::check($temporary, $account->fresh()->password));
        $this->assertTrue($account->fresh()->must_change_password);
        $this->assertFalse(Hash::check('Old Secure!2026', $account->fresh()->password));
    }

    public function test_admin_can_delete_a_generated_account_without_deleting_academic_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create(['student_number' => 'DEL-100', 'name' => 'Delete, Student', 'official_email' => 'del-100@student.fiatlux.local', 'masterlist_active' => true]);
        $account = User::factory()->create(['role' => 'student', 'student_number' => $student->student_number, 'email' => $student->official_email]);

        $this->actingAs($admin)->delete(route('account-creation.destroy-account', $student), ['confirm_delete' => '1'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $account->id, 'role' => 'archived_student']);
        $this->assertDatabaseHas('students', ['student_number' => $student->student_number]);
        $this->assertSame('del-100@student.fiatlux.local', $student->fresh()->official_email);
    }

    public function test_admin_can_delete_all_uploaded_account_data_without_deleting_student_records(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::disk('local')->put('student-masterlists/upload.pdf', 'pdf');
        $import = StudentMasterlistImport::create([
            'school_year' => '2024-2025', 'original_name' => 'upload.pdf', 'file_sha256' => hash('sha256', 'upload'),
            'storage_path' => 'student-masterlists/upload.pdf', 'imported_by' => $admin->id,
        ]);
        $student = Student::create([
            'student_number' => 'PURGE-100', 'name' => 'Keep, Academic', 'official_email' => 'purge-100@student.fiatlux.local',
            'masterlist_import_id' => $import->id, 'masterlist_school_year' => '2024-2025', 'masterlist_active' => true,
        ]);
        User::factory()->create(['role' => 'student', 'student_number' => $student->student_number, 'email' => $student->official_email]);

        $this->actingAs($admin)->delete(route('account-creation.purge'), ['confirm_purge' => '1'])
            ->assertRedirect(route('account-creation.index'))->assertSessionHas('success');

        $this->assertDatabaseCount('student_masterlist_imports', 0);
        $this->assertDatabaseMissing('users', ['student_number' => $student->student_number]);
        $this->assertDatabaseHas('students', ['student_number' => $student->student_number, 'masterlist_active' => false]);
        Storage::disk('local')->assertMissing('student-masterlists/upload.pdf');
    }
}
