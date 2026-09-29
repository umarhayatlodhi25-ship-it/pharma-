<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an authenticated user for tests
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /**
     * Test unauthenticated users cannot access patient routes.
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/patients');
        $response->assertRedirect('/login');

        $response = $this->get('/patients/create');
        $response->assertRedirect('/login');

        $response = $this->post('/patients', []);
        $response->assertRedirect('/login');
    }

    /**
     * Test empty form validation errors.
     */
    public function test_empty_form_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/patients', []);

        $response->assertSessionHasErrors(['name', 'age', 'gender']);
    }

    /**
     * Test invalid gender and age boundaries.
     */
    public function test_invalid_data_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/patients', [
                'name'   => 'Test Patient',
                'age'    => 200, // max is 150
                'gender' => 'Unknown', // only Male, Female, Other
            ]);

        $response->assertSessionHasErrors(['age', 'gender']);

        $responseNegativeAge = $this->actingAs($this->user)
            ->post('/patients', [
                'name'   => 'Test Patient',
                'age'    => -5, // min is 0
                'gender' => 'Male',
            ]);

        $responseNegativeAge->assertSessionHasErrors(['age']);
    }

    /**
     * Test valid patient registration and redirection.
     */
    public function test_valid_patient_registration(): void
    {
        $payload = [
            'name'                => 'Ahmad Khan',
            'father_husband_name' => 'Muhammad Khan',
            'age'                 => 28,
            'gender'              => 'Male',
            'phone'               => '03001234567',
            'cnic'                => '35201-1234567-1',
            'address'             => 'Model Town, Lahore',
        ];

        $response = $this->actingAs($this->user)
            ->post('/patients', $payload);

        $patient = Patient::where('name', 'Ahmad Khan')->first();
        $this->assertNotNull($patient);

        $response->assertRedirect(route('patients.show', $patient->id));
        $response->assertSessionHas('success', 'Patient registered successfully.');

        $this->assertDatabaseHas('patients', [
            'name'                => 'Ahmad Khan',
            'father_husband_name' => 'Muhammad Khan',
            'age'                 => 28,
            'gender'              => 'Male',
            'phone'               => '03001234567',
            'cnic'                => '35201-1234567-1',
            'address'             => 'Model Town, Lahore',
        ]);
    }

    /**
     * Test automatic sequential patient number generation (PT-00001, PT-00002...).
     */
    public function test_sequential_patient_number_generation(): void
    {
        $p1 = Patient::create([
            'name'   => 'First Patient',
            'age'    => 25,
            'gender' => 'Male',
        ]);

        $p2 = Patient::create([
            'name'   => 'Second Patient',
            'age'    => 30,
            'gender' => 'Female',
        ]);

        $this->assertMatchesRegularExpression('/^PT-\d{5}$/', $p1->patient_number);
        $this->assertMatchesRegularExpression('/^PT-\d{5}$/', $p2->patient_number);

        // Extract numeric suffixes
        $n1 = intval(substr($p1->patient_number, 3));
        $n2 = intval(substr($p2->patient_number, 3));
        $this->assertEquals($n1 + 1, $n2);
    }

    /**
     * Test patient list page displays registered patients.
     */
    public function test_patient_list_displays_patients(): void
    {
        $patient = Patient::create([
            'name'                => 'Zainab Bibi',
            'father_husband_name' => 'Ali Raza',
            'age'                 => 40,
            'gender'              => 'Female',
            'phone'               => '03219876543',
            'cnic'                => '35202-9876543-2',
        ]);

        $response = $this->actingAs($this->user)->get('/patients');

        $response->assertStatus(200);
        $response->assertSee('Patient Management');
        $response->assertSee($patient->patient_number);
        $response->assertSee('Zainab Bibi');
        $response->assertSee('Ali Raza');
        $response->assertSee('03219876543');
    }

    /**
     * Test search functionality works for patient number, name, phone, CNIC.
     */
    public function test_patient_search(): void
    {
        $p1 = Patient::create([
            'name'    => 'Bilal Ashraf',
            'age'     => 22,
            'gender'  => 'Male',
            'phone'   => '03115554433',
            'cnic'    => '35201-5554433-1',
        ]);

        $p2 = Patient::create([
            'name'    => 'Fatima Noor',
            'age'     => 31,
            'gender'  => 'Female',
            'phone'   => '03449998877',
            'cnic'    => '35201-9998877-2',
        ]);

        // Search by name
        $responseName = $this->actingAs($this->user)->get('/patients?search=Bilal');
        $responseName->assertSee('Bilal Ashraf');
        $responseName->assertDontSee('Fatima Noor');

        // Search by Patient ID
        $responseNumber = $this->actingAs($this->user)->get('/patients?search=' . $p2->patient_number);
        $responseNumber->assertSee('Fatima Noor');
        $responseNumber->assertDontSee('Bilal Ashraf');

        // Search by Phone
        $responsePhone = $this->actingAs($this->user)->get('/patients?search=03115554433');
        $responsePhone->assertSee('Bilal Ashraf');
        $responsePhone->assertDontSee('Fatima Noor');

        // Search by CNIC
        $responseCnic = $this->actingAs($this->user)->get('/patients?search=9998877');
        $responseCnic->assertSee('Fatima Noor');
        $responseCnic->assertDontSee('Bilal Ashraf');
    }

    /**
     * Test patient profile show page.
     */
    public function test_patient_profile_display(): void
    {
        $patient = Patient::create([
            'name'                => 'Usman Ghani',
            'father_husband_name' => 'Abdul Ghani',
            'age'                 => 55,
            'gender'              => 'Male',
            'phone'               => '03337778899',
            'cnic'                => '35201-7778899-3',
            'address'             => 'Gulberg III, Lahore',
        ]);

        $response = $this->actingAs($this->user)->get(route('patients.show', $patient->id));

        $response->assertStatus(200);
        $response->assertSee('Patient Profile Details');
        $response->assertSee($patient->patient_number);
        $response->assertSee('Usman Ghani');
        $response->assertSee('Abdul Ghani');
        $response->assertSee('55 Years');
        $response->assertSee('03337778899');
        $response->assertSee('35201-7778899-3');
        $response->assertSee('Gulberg III, Lahore');
        $response->assertSee('Back to Patients');
    }
}
