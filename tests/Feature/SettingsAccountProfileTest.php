<?php

namespace Tests\Feature;

use App\Livewire\Opd\TokenManagement;
use App\Models\AccountProfile;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsAccountProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $pharmacistUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->pharmacistUser = User::factory()->create([
            'role' => 'pharmacist',
        ]);

        Storage::fake('public');
        AccountProfile::clearCache();
    }

    public function test_settings_page_requires_authentication(): void
    {
        $response = $this->get('/settings');
        $response->assertRedirect('/login');
    }

    public function test_settings_page_restricted_from_non_admin_users(): void
    {
        $response = $this->actingAs($this->pharmacistUser)->get('/settings');
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_settings_page_loads_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/settings');
        $response->assertStatus(200);
        $response->assertSee('Account / Organization Profile');
        $response->assertSee('Organization Logo');
        $response->assertSee('Save Changes');
    }

    public function test_account_profile_update_and_persistence(): void
    {
        $response = $this->actingAs($this->adminUser)->put('/settings/profile', [
            'account_name'        => 'ABC Medical & Diagnostic Center',
            'phone'               => '0300-1234567',
            'email'               => 'info@example.com',
            'address'             => 'Main Road',
            'city'                => 'Karachi',
            'website'             => 'https://abc-medical.test',
            'registration_number' => 'PMC-99882',
            'footer_text'         => 'Thank you for choosing our healthcare services.',
            'currency'            => 'PKR',
            'timezone'            => 'Asia/Karachi',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'profile']));
        $response->assertSessionHas('success');

        // Check database persistence
        $this->assertDatabaseHas('account_profiles', [
            'account_name' => 'ABC Medical & Diagnostic Center',
            'phone'        => '0300-1234567',
            'email'        => 'info@example.com',
            'address'      => 'Main Road',
            'city'         => 'Karachi',
            'currency'     => 'PKR',
            'timezone'     => 'Asia/Karachi',
        ]);

        // Check global helper / singleton
        AccountProfile::clearCache();
        $current = account_profile();
        $this->assertEquals('ABC Medical & Diagnostic Center', $current->account_name);
        $this->assertEquals('Main Road, Karachi', $current->formatted_address);
        $this->assertEquals('PKR', $current->currency);
        $this->assertEquals('Asia/Karachi', $current->timezone);
    }

    public function test_logo_upload_replacement_and_removal(): void
    {
        // 1. Upload initial logo
        $logoFile = UploadedFile::fake()->image('hospital_logo.png', 300, 300);

        $response = $this->actingAs($this->adminUser)->put('/settings/profile', [
            'account_name' => 'City Hospital Lahore',
            'currency'     => 'PKR',
            'timezone'     => 'Asia/Karachi',
            'logo'         => $logoFile,
        ]);

        $response->assertRedirect();
        AccountProfile::clearCache();

        $profile = account_profile();
        $this->assertNotNull($profile->logo);
        Storage::disk('public')->assertExists($profile->logo);
        $this->assertTrue($profile->hasLogo());
        $this->assertNotNull($profile->logo_url);

        $firstLogoPath = $profile->logo;

        // 2. Replace with new logo
        $newLogoFile = UploadedFile::fake()->image('new_brand_logo.jpg', 400, 400);

        $this->actingAs($this->adminUser)->put('/settings/profile', [
            'account_name' => 'City Hospital Lahore',
            'currency'     => 'PKR',
            'timezone'     => 'Asia/Karachi',
            'logo'         => $newLogoFile,
        ]);

        AccountProfile::clearCache();
        $profile->refresh();

        // Old file must be deleted, new file must exist
        Storage::disk('public')->assertMissing($firstLogoPath);
        Storage::disk('public')->assertExists($profile->logo);
        $this->assertNotEquals($firstLogoPath, $profile->logo);

        // 3. Remove logo via deleteLogo endpoint
        $deleteLogoResponse = $this->actingAs($this->adminUser)->delete('/settings/profile/logo');
        $deleteLogoResponse->assertRedirect();

        AccountProfile::clearCache();
        $profile->refresh();
        $this->assertNull($profile->logo);
        $this->assertFalse($profile->hasLogo());
        $this->assertNull($profile->logo_url);
    }

    public function test_account_profile_validation_rules(): void
    {
        // Missing required account_name
        $response = $this->actingAs($this->adminUser)->put('/settings/profile', [
            'account_name' => '',
            'currency'     => 'PKR',
            'timezone'     => 'Asia/Karachi',
        ]);
        $response->assertSessionHasErrors(['account_name']);

        // Invalid email
        $response = $this->actingAs($this->adminUser)->put('/settings/profile', [
            'account_name' => 'Valid Name',
            'email'        => 'not-a-valid-email',
            'currency'     => 'PKR',
            'timezone'     => 'Asia/Karachi',
        ]);
        $response->assertSessionHasErrors(['email']);

        // Invalid logo format (e.g. text file instead of image)
        $txtFile = UploadedFile::fake()->create('document.txt', 100);
        $response = $this->actingAs($this->adminUser)->put('/settings/profile', [
            'account_name' => 'Valid Name',
            'currency'     => 'PKR',
            'timezone'     => 'Asia/Karachi',
            'logo'         => $txtFile,
        ]);
        $response->assertSessionHasErrors(['logo']);
    }

    public function test_opd_token_printable_area_renders_centralized_account_profile(): void
    {
        // Setup customized account profile
        $profile = AccountProfile::current();
        $profile->update([
            'account_name' => 'Al-Shifa Healthcare Center',
            'phone'        => '042-35889900',
            'address'      => 'Gulberg III',
            'city'         => 'Lahore',
            'footer_text'  => 'Emergency OPD Services 24/7',
        ]);
        AccountProfile::clearCache();

        $doctor = Doctor::create([
            'name'             => 'Dr. Haris',
            'specialization'   => 'Cardiologist',
            'consultation_fee' => 1500.00,
            'status'           => 'active',
        ]);

        $patient = Patient::create([
            'name'   => 'Kashif Ali',
            'phone'  => '03001234567',
            'gender' => 'Male',
            'age'    => 40,
        ]);

        // Generate token and print
        Livewire::actingAs($this->adminUser)
            ->test(TokenManagement::class)
            ->set('doctor_id', (string) $doctor->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $patient->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors()
            ->call('printToken')
            ->assertSee('Al-Shifa Healthcare Center')
            ->assertSee('Gulberg III, Lahore')
            ->assertSee('042-35889900')
            ->assertSee('Emergency OPD Services 24/7');
    }
}
