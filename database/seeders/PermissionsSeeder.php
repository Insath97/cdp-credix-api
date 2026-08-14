<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            /* Activity Management */
            ['name' => 'Activity Index', 'group_name' => 'Activity Management Permissions'],
            ['name' => 'Activity Show', 'group_name' => 'Activity Management Permissions'],

            /* Access Management */
            ['name' => 'Permission Index', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Permission Create', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Permission Update', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Permission Delete', 'group_name' => 'Access Management Permissions'],

            ['name' => 'Role Index', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Create', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Update', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Delete', 'group_name' => 'Access Management Permissions'],

            /* User Management */
            ['name' => 'User Index', 'group_name' => 'User Management Permissions'],
            ['name' => 'User Create', 'group_name' => 'User Management Permissions'],
            ['name' => 'User Update', 'group_name' => 'User Management Permissions'],
            ['name' => 'User Delete', 'group_name' => 'User Management Permissions'],
            ['name' => 'User Toggle Status', 'group_name' => 'User Management Permissions'],

            /* Employee Management */
            ['name' => 'Employee Index', 'group_name' => 'Employee Management Permissions'],

            /* Country Management */
            ['name' => 'Country Index', 'group_name' => 'Country Management Permissions'],
            ['name' => 'Country Create', 'group_name' => 'Country Management Permissions'],
            ['name' => 'Country Update', 'group_name' => 'Country Management Permissions'],
            ['name' => 'Country Delete', 'group_name' => 'Country Management Permissions'],
            ['name' => 'Country Toggle Status', 'group_name' => 'Country Management Permissions'],

            /* Province Management */
            ['name' => 'Province Index', 'group_name' => 'Province Management Permissions'],
            ['name' => 'Province Create', 'group_name' => 'Province Management Permissions'],
            ['name' => 'Province Update', 'group_name' => 'Province Management Permissions'],
            ['name' => 'Province Delete', 'group_name' => 'Province Management Permissions'],
            ['name' => 'Province Toggle Status', 'group_name' => 'Province Management Permissions'],

            /* Zonal Management */
            ['name' => 'Zonal Index', 'group_name' => 'Zonal Management Permissions'],
            ['name' => 'Zonal Create', 'group_name' => 'Zonal Management Permissions'],
            ['name' => 'Zonal Update', 'group_name' => 'Zonal Management Permissions'],
            ['name' => 'Zonal Delete', 'group_name' => 'Zonal Management Permissions'],
            ['name' => 'Zonal Toggle Status', 'group_name' => 'Zonal Management Permissions'],

            /* Region Management */
            ['name' => 'Region Index', 'group_name' => 'Region Management Permissions'],
            ['name' => 'Region Create', 'group_name' => 'Region Management Permissions'],
            ['name' => 'Region Update', 'group_name' => 'Region Management Permissions'],
            ['name' => 'Region Delete', 'group_name' => 'Region Management Permissions'],
            ['name' => 'Region Toggle Status', 'group_name' => 'Region Management Permissions'],

            /* Branch Management */
            ['name' => 'Branch Index', 'group_name' => 'Branch Management Permissions'],
            ['name' => 'Branch Create', 'group_name' => 'Branch Management Permissions'],
            ['name' => 'Branch Update', 'group_name' => 'Branch Management Permissions'],
            ['name' => 'Branch Delete', 'group_name' => 'Branch Management Permissions'],
            ['name' => 'Branch Toggle Status', 'group_name' => 'Branch Management Permissions'],

            /* Department Management */
            ['name' => 'Department Index', 'group_name' => 'Department Management Permissions'],
            ['name' => 'Department Create', 'group_name' => 'Department Management Permissions'],
            ['name' => 'Department Update', 'group_name' => 'Department Management Permissions'],
            ['name' => 'Department Delete', 'group_name' => 'Department Management Permissions'],
            ['name' => 'Department Toggle Status', 'group_name' => 'Department Management Permissions'],

            /* Designation Management */
            ['name' => 'Designation Index', 'group_name' => 'Designation Management Permissions'],
            ['name' => 'Designation Create', 'group_name' => 'Designation Management Permissions'],
            ['name' => 'Designation Update', 'group_name' => 'Designation Management Permissions'],
            ['name' => 'Designation Delete', 'group_name' => 'Designation Management Permissions'],
            ['name' => 'Designation Toggle Status', 'group_name' => 'Designation Management Permissions'],

            /* Group Management */
            ['name' => 'Group Index', 'group_name' => 'Group Management Permissions'],
            ['name' => 'Group Create', 'group_name' => 'Group Management Permissions'],
            ['name' => 'Group Update', 'group_name' => 'Group Management Permissions'],
            ['name' => 'Group Delete', 'group_name' => 'Group Management Permissions'],
            ['name' => 'Group Toggle Status', 'group_name' => 'Group Management Permissions'],

            /* Customer Management */
            ['name' => 'Customer Index', 'group_name' => 'Customer Management Permissions'],
            ['name' => 'Customer Create', 'group_name' => 'Customer Management Permissions'],
            ['name' => 'Customer Update', 'group_name' => 'Customer Management Permissions'],
            ['name' => 'Customer Delete', 'group_name' => 'Customer Management Permissions'],
            ['name' => 'Customer Restore', 'group_name' => 'Customer Management Permissions'],
            ['name' => 'Customer Force Delete', 'group_name' => 'Customer Management Permissions'],
            ['name' => 'Customer Toggle Status', 'group_name' => 'Customer Management Permissions'],

            /* Customer Bank Detail Management */
            ['name' => 'Customer Bank Detail Index', 'group_name' => 'Customer Bank Detail Management Permissions'],
            ['name' => 'Customer Bank Detail Create', 'group_name' => 'Customer Bank Detail Management Permissions'],
            ['name' => 'Customer Bank Detail Update', 'group_name' => 'Customer Bank Detail Management Permissions'],
            ['name' => 'Customer Bank Detail Delete', 'group_name' => 'Customer Bank Detail Management Permissions'],
            ['name' => 'Customer Bank Detail Toggle Status', 'group_name' => 'Customer Bank Detail Management Permissions'],

            /* Guarantor Management */
            ['name' => 'Guarantor Index', 'group_name' => 'Guarantor Management Permissions'],
            ['name' => 'Guarantor Create', 'group_name' => 'Guarantor Management Permissions'],
            ['name' => 'Guarantor Update', 'group_name' => 'Guarantor Management Permissions'],
            ['name' => 'Guarantor Delete', 'group_name' => 'Guarantor Management Permissions'],

            /* Application Management */
            ['name' => 'Application Index', 'group_name' => 'Application Management Permissions'],
            ['name' => 'Application Create', 'group_name' => 'Application Management Permissions'],
            ['name' => 'Application Update', 'group_name' => 'Application Management Permissions'],
            ['name' => 'Application Delete', 'group_name' => 'Application Management Permissions'],

            /* Application History Management */
            ['name' => 'Application History Index', 'group_name' => 'Application History Management Permissions'],
            ['name' => 'Application History Create', 'group_name' => 'Application History Management Permissions'],
            ['name' => 'Application History Update', 'group_name' => 'Application History Management Permissions'],
            ['name' => 'Application History Delete', 'group_name' => 'Application History Management Permissions'],

            /* Fixed Asset Management */
            ['name' => 'Fixed Asset Index', 'group_name' => 'Fixed Asset Management Permissions'],
            ['name' => 'Fixed Asset Create', 'group_name' => 'Fixed Asset Management Permissions'],
            ['name' => 'Fixed Asset Update', 'group_name' => 'Fixed Asset Management Permissions'],
            ['name' => 'Fixed Asset Delete', 'group_name' => 'Fixed Asset Management Permissions'],

            /* Moving Asset Management */
            ['name' => 'Moving Asset Index', 'group_name' => 'Moving Asset Management Permissions'],
            ['name' => 'Moving Asset Create', 'group_name' => 'Moving Asset Management Permissions'],
            ['name' => 'Moving Asset Update', 'group_name' => 'Moving Asset Management Permissions'],
            ['name' => 'Moving Asset Delete', 'group_name' => 'Moving Asset Management Permissions'],

            /* Liability Management */
            ['name' => 'Liability Index', 'group_name' => 'Liability Management Permissions'],
            ['name' => 'Liability Create', 'group_name' => 'Liability Management Permissions'],
            ['name' => 'Liability Update', 'group_name' => 'Liability Management Permissions'],
            ['name' => 'Liability Delete', 'group_name' => 'Liability Management Permissions'],
            ['name' => 'Liability Toggle Status', 'group_name' => 'Liability Management Permissions'],

            /* Document Management */
            ['name' => 'Document Index', 'group_name' => 'Document Management Permissions'],
            ['name' => 'Document Create', 'group_name' => 'Document Management Permissions'],
            ['name' => 'Document Update', 'group_name' => 'Document Management Permissions'],
            ['name' => 'Document Delete', 'group_name' => 'Document Management Permissions'],

            /* Loan Term Management */
            ['name' => 'Loan Term Index', 'group_name' => 'Loan Term Management Permissions'],
            ['name' => 'Loan Term Create', 'group_name' => 'Loan Term Management Permissions'],
            ['name' => 'Loan Term Update', 'group_name' => 'Loan Term Management Permissions'],
            ['name' => 'Loan Term Delete', 'group_name' => 'Loan Term Management Permissions'],
            ['name' => 'Loan Term Toggle Status', 'group_name' => 'Loan Term Management Permissions'],

            /* Loan Type Management */
            ['name' => 'Loan Type Index', 'group_name' => 'Loan Type Management Permissions'],
            ['name' => 'Loan Type Create', 'group_name' => 'Loan Type Management Permissions'],
            ['name' => 'Loan Type Update', 'group_name' => 'Loan Type Management Permissions'],
            ['name' => 'Loan Type Delete', 'group_name' => 'Loan Type Management Permissions'],
            ['name' => 'Loan Type Toggle Status', 'group_name' => 'Loan Type Management Permissions'],

            /* Loan Product Management */
            ['name' => 'Loan Product Index', 'group_name' => 'Loan Product Management Permissions'],
            ['name' => 'Loan Product Create', 'group_name' => 'Loan Product Management Permissions'],
            ['name' => 'Loan Product Update', 'group_name' => 'Loan Product Management Permissions'],
            ['name' => 'Loan Product Delete', 'group_name' => 'Loan Product Management Permissions'],
            ['name' => 'Loan Product Toggle Status', 'group_name' => 'Loan Product Management Permissions'],

            /* Loan Application Management */
            ['name' => 'Loan Application Index', 'group_name' => 'Loan Application Management Permissions'],
            ['name' => 'Loan Application Create', 'group_name' => 'Loan Application Management Permissions'],
            ['name' => 'Loan Application Update', 'group_name' => 'Loan Application Management Permissions'],
            ['name' => 'Loan Application Delete', 'group_name' => 'Loan Application Management Permissions'],
            ['name' => 'Loan Application Toggle Status', 'group_name' => 'Loan Application Management Permissions'],
            ['name' => 'Loan Application Verify', 'group_name' => 'Loan Application Management Permissions'],
            ['name' => 'Loan Application Approve', 'group_name' => 'Loan Application Management Permissions'],
            ['name' => 'Loan Application Reject', 'group_name' => 'Loan Application Management Permissions'],
            ['name' => 'Loan Application Disburse', 'group_name' => 'Loan Application Management Permissions'],
            ['name' => 'Loan Application Cancel', 'group_name' => 'Loan Application Management Permissions'],

            /* Loan Application Guarantor Management */
            ['name' => 'Loan Application Guarantor Index', 'group_name' => 'Loan Application Guarantor Management Permissions'],
            ['name' => 'Loan Application Guarantor Create', 'group_name' => 'Loan Application Guarantor Management Permissions'],
            ['name' => 'Loan Application Guarantor Update', 'group_name' => 'Loan Application Guarantor Management Permissions'],
            ['name' => 'Loan Application Guarantor Delete', 'group_name' => 'Loan Application Guarantor Management Permissions'],

            /* Loan Application Fixed Asset Management */
            ['name' => 'Loan Application Fixed Asset Index', 'group_name' => 'Loan Application Fixed Asset Management Permissions'],
            ['name' => 'Loan Application Fixed Asset Create', 'group_name' => 'Loan Application Fixed Asset Management Permissions'],
            ['name' => 'Loan Application Fixed Asset Delete', 'group_name' => 'Loan Application Fixed Asset Management Permissions'],

            /* Loan Application Moving Asset Management */
            ['name' => 'Loan Application Moving Asset Index', 'group_name' => 'Loan Application Moving Asset Management Permissions'],
            ['name' => 'Loan Application Moving Asset Create', 'group_name' => 'Loan Application Moving Asset Management Permissions'],
            ['name' => 'Loan Application Moving Asset Delete', 'group_name' => 'Loan Application Moving Asset Management Permissions'],

            /* Loan Installment Management */
            ['name' => 'Loan Installment Index', 'group_name' => 'Loan Installment Management Permissions'],
            ['name' => 'Loan Installment Create', 'group_name' => 'Loan Installment Management Permissions'],
            ['name' => 'Loan Installment Update', 'group_name' => 'Loan Installment Management Permissions'],
            ['name' => 'Loan Installment Delete', 'group_name' => 'Loan Installment Management Permissions'],

            /* Payment Management */
            ['name' => 'Payment Index', 'group_name' => 'Payment Management Permissions'],
            ['name' => 'Payment Create', 'group_name' => 'Payment Management Permissions'],
            ['name' => 'Payment Update', 'group_name' => 'Payment Management Permissions'],
            ['name' => 'Payment Delete', 'group_name' => 'Payment Management Permissions'],

            /* Recovery Case Management */
            ['name' => 'Recovery Case Index', 'group_name' => 'Recovery Case Management Permissions'],
            ['name' => 'Recovery Case Create', 'group_name' => 'Recovery Case Management Permissions'],
            ['name' => 'Recovery Case Update', 'group_name' => 'Recovery Case Management Permissions'],
            ['name' => 'Recovery Case Delete', 'group_name' => 'Recovery Case Management Permissions'],

            /* Recovery Activity Management */
            ['name' => 'Recovery Activity Index', 'group_name' => 'Recovery Activity Management Permissions'],
            ['name' => 'Recovery Activity Create', 'group_name' => 'Recovery Activity Management Permissions'],
            ['name' => 'Recovery Activity Update', 'group_name' => 'Recovery Activity Management Permissions'],
            ['name' => 'Recovery Activity Delete', 'group_name' => 'Recovery Activity Management Permissions'],

            /* Recovery Agent Management */
            ['name' => 'Recovery Agent Index', 'group_name' => 'Recovery Agent Management Permissions'],
            ['name' => 'Recovery Agent Create', 'group_name' => 'Recovery Agent Management Permissions'],
            ['name' => 'Recovery Agent Update', 'group_name' => 'Recovery Agent Management Permissions'],
            ['name' => 'Recovery Agent Delete', 'group_name' => 'Recovery Agent Management Permissions'],

            /* External Recovery Agent Management */
            ['name' => 'External Recovery Agent Index', 'group_name' => 'External Recovery Agent Management Permissions'],
            ['name' => 'External Recovery Agent Create', 'group_name' => 'External Recovery Agent Management Permissions'],
            ['name' => 'External Recovery Agent Update', 'group_name' => 'External Recovery Agent Management Permissions'],
            ['name' => 'External Recovery Agent Delete', 'group_name' => 'External Recovery Agent Management Permissions'],

            /* Notification Management */
            ['name' => 'Notification Index', 'group_name' => 'Notification Management Permissions'],

            /* System Settings Management */
            ['name' => 'Setting Index', 'group_name' => 'System Settings Permissions'],
            ['name' => 'Setting Update', 'group_name' => 'System Settings Permissions'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'group_name' => $permission['group_name'],
                'guard_name' => 'api',
            ]);
        }

        $role = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'Super Admin']);

        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);

        Role::firstOrCreate(['guard_name' => 'api', 'name' => 'Employee']);
    }
}
