<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$configrole = ['Manage Employees','Manage User','Manage Client','Manage Leave','Manage Project','Manage Holiday','Employee Project Section','Team List','Monthly Report Team Members'];
		$configadmin = ['superadmin'];
		
		$status = ['Open','Ready','In Process','Testing','Done','Completed','Closed'];
		$i = 1;
		$roles = [];
        // foreach ($configrole as $key => $value) {
            // $role = \App\Models\Role::create([
                // 'title' => $value
            // ]);
			// $roles[] = $i;
			// $i++;
		// }

		// foreach ($configadmin as $key => $value) {
			// $user = \App\Models\User::create([
				// 'name' => ucwords(str_replace('_', ' ', $value)),
				// 'email' => $value.'@app.com',
				// 'password' => 'password',
				// 'designation' => strtoupper('superadmin')
			// ]);
			
			// foreach ($roles as $skey => $svalue) {
				// $Permission = \App\Models\Permission::create([
					// 'role_id' => $svalue,
					// 'user_id' => $user->id,
					// 'permission' => '1,2,3,4,5',
				// ]);
			// }
		// }
		
		
		foreach ($status as $key => $value) {
			\App\Models\ProjectStatu::create([
				'status' => $value
			]);
		}
    }
}
