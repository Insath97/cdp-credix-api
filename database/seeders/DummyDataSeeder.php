<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\Group;
use App\Models\Province;
use App\Models\Zonal;
use App\Models\Region;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Customer;
use App\Models\Application;
use App\Models\ApplicationHistory;
use App\Models\FixedAssests;
use App\Models\MovingAssests;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Country
        $country = Country::create([
            'name' => 'Sri Lanka',
            'code' => 'LKA',
            'description' => 'Democratic Socialist Republic of Sri Lanka',
            'is_active' => true,
        ]);

        // 2. Group
        $group = Group::create([
            'name' => 'Credix Group',
            'code' => 'CRD',
            'description' => 'Main financial services group',
            'is_active' => true,
        ]);

        // 3. Province
        $province = Province::create([
            'name' => 'Western Province',
            'code' => 'WP',
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        // 4. Zonal
        $zonal = Zonal::create([
            'name' => 'Colombo Zone',
            'code' => 'ZN-COL',
            'province_id' => $province->id,
            'is_active' => true,
        ]);

        // 5. Region
        $region = Region::create([
            'name' => 'Colombo Region 1',
            'code' => 'RG-COL1',
            'zonal_id' => $zonal->id,
            'is_active' => true,
        ]);

        // 6. Branch
        $branch = Branch::create([
            'group_id' => $group->id,
            'province_id' => $province->id,
            'zone_id' => $zonal->id,
            'region_id' => $region->id,
            'name' => 'Colombo Head Office',
            'code' => 'BR-COL',
            'address_line1' => 'No. 123, Galle Road',
            'address_line2' => 'Colombo 03',
            'city' => 'Colombo',
            'postal_code' => '00300',
            'phone_primary' => '0112345678',
            'phone_secondary' => '0112345679',
            'email' => 'colombo@credix.com',
            'fax' => '0112345680',
            'opening_date' => '2020-01-01',
            'branch_type' => 'main',
            'latitude' => 6.9271,
            'longitude' => 79.8612,
            'is_active' => true,
            'is_head_office' => true,
        ]);

        // 7. Department
        $department = Department::create([
            'name' => 'Credit Operations',
            'code' => 'DEPT-CRED',
            'description' => 'Handles credit evaluation and applications',
            'is_active' => true,
        ]);

        // 8. Designation
        $designation = Designation::create([
            'department_id' => $department->id,
            'name' => 'Credit Manager',
            'code' => 'DESG-CM',
            'level' => 'Manager',
            'order_weight' => 1,
            'description' => 'Manages credit department operations',
            'is_active' => true,
        ]);

        // 9. Customer
        $customer = Customer::create([
            'customer_id' => 'CUST-0001',
            'customer_code' => 'CUS0001',
            'full_name' => 'Alexander Peiris',
            'name_with_initials' => 'A. Peiris',
            'id_type' => 'NIC',
            'id_number' => '199411223345',
            'date_of_birth' => '1994-04-12',
            'address_line_1' => 'No. 88, Flower Road',
            'address_line_2' => 'Colombo 07',
            'landmark' => 'Opposite Viharamahadevi Park',
            'city' => 'Colombo',
            'state' => 'Western',
            'country' => 'Sri Lanka',
            'postal_code' => '00700',
            'phone_primary' => '0744125923',
            'phone_secondary' => '0112987654',
            'email' => 'alexs.peiris@example.com',
            'have_whatsapp' => true,
            'whatsapp_number' => '0773344557',
            'preferred_language' => 'Tamil',
            'employment_status' => 'Employed',
            'occupation' => 'Operations Manager',
            'employer_name' => 'Lanka Logistics Ltd',
            'employer_address_line1' => 'No. 12, Harbour Road',
            'employer_address_line2' => 'Colombo 13',
            'employer_city' => 'Colombo',
            'employer_state' => 'Western',
            'employer_country' => 'Sri Lanka',
            'employer_postal_code' => '01300',
            'employer_phone' => '0112999888',
            'employer_email' => 'hr@lankalogistics.lk',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        // Create a user for the customer so they can log in
        $customerUser = User::create([
            'name' => 'Alexander Peiris',
            'username' => '199411223345', // NIC as username
            'email' => 'alexs.peiris@example.com',
            'password' => Hash::make('password'),
            'user_type' => 'customer',
            'customer_id' => $customer->id,
            'is_active' => true,
            'can_login' => true,
        ]);

        // 10. Application
        $application = Application::create([
            'application_no' => 'APP/2026/0001',
            'application_type' => 'loan',
            'branch' => 'Colombo Head Office',
            'loan_type' => 'Personal Loan',
            'requested_amount' => 500000.00,
            'purpose' => 'Personal expenses and home renovation',
            'repayment_period_months' => 24,
            'monthly_repayment_date' => '10',
            'status' => 'pending',
        ]);

        // 11. ApplicationHistory
        $applicationHistory = ApplicationHistory::create([
            'application_id' => $application->id,
            'customer_id' => $customer->id,
            'application_no' => 'APP/2026/0001',
            'application_type' => 'loan',
            'role' => 'primary',
            'status' => 'pending',
            'basic_salary' => 120000.00,
            'fixed_allowances' => 15000.00,
            'other_allowances' => 5000.00,
            'other_income' => 10000.00,
            'total_monthly_income' => 150000.00,
            'household_expenses' => 30000.00,
            'rent_expense' => 15000.00,
            'insurance_premiums' => 5000.00,
            'other_expenses' => 10000.00,
            'total_monthly_expenses' => 60000.00,
            'requested_amount' => 500000.00,
            'purpose' => 'Personal expenses and home renovation',
            'recorded_at' => now(),
        ]);

        // 12. Fixed Assets
        $fixedAsset = FixedAssests::create([
            'customer_id' => $customer->id,
            'owner_name' => 'Alexander Peiris',
            'property_location' => 'Moratuwa',
            'extent' => '15 Perches Land',
            'market_value' => 25000000.00,
            'is_mortaged' => false,
            'is_active' => true,
        ]);

        // 13. Moving Assets
        $movingAsset = MovingAssests::create([
            'customer_id' => $customer->id,
            'assest_category' => 'vehicle',
            'owner_name' => 'Alexander Peiris',
            'make_model' => 'Honda Vezel',
            'company_name' => '',
            'no_of_shares' => null,
            'par_value' => null,
            'registation_no' => 'WP CAB-8899',
            'market_value' => 9200000.00,
            'mortgage_lease_hire_status' => 'none',
            'is_active' => true,
        ]);

        // 14. Document (Find development admin user or customer user to upload)
        $adminUser = User::where('user_type', 'admin')->first();
        $uploaderId = $adminUser ? $adminUser->id : $customerUser->id;

        $document = Document::create([
            'document_type' => 'nic_copy',
            'is_mandatory' => true,
            'document_name' => 'Alexander NIC Copy',
            'file_path' => 'documents/nic_copy_alexander.pdf',
            'uploaded_by' => $uploaderId,
            'uploaded_at' => now(),
            'status' => 'active',
            'remarks' => 'NIC verified and approved by Credit Officer',
            'is_active' => true,
        ]);
    }
}
