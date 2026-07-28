<?php

namespace Database\Seeders;

use App\Support\GrantablePermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (GrantablePermissions::cles() as $cle) {
            Permission::findOrCreate($cle, 'web');
        }
    }
}
