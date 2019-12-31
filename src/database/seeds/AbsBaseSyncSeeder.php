<?php

namespace Abs\DeployHelper;

use Illuminate\Database\Seeder;

class AbsBaseSyncSeeder extends Seeder {

	protected function commonCommands() {
		dump('Executing sudo composer update');
		shell_exec('sudo composer update');

		dump('Executing sudo composer dump-autoload');
		shell_exec('sudo composer dump-autoload');

		dump('Executing php artisan migrate');
		shell_exec('php artisan migrate');

		$this->call(\PermissionSeeder::class);
		$this->call(\ConfigTypeConfigSeeder::class);
		$this->call(\EntityTypeSeeder::class);
		$this->call(\PkgPermissionSeeder::class);

		dump('Setting 777 Permission to storage/');
		shell_exec('sudo chmod -R 777 storage/');

		dump('Setting 777 Permission to bootstrap/');
		shell_exec('sudo chmod -R 777 bootstrap/');

		dump('php artisan cache:clear');
		shell_exec('sudo php artisan cache:clear');

		dump('sudo php artisan config:cache');
		shell_exec('sudo php artisan config:cache');

		dump('sudo php artisan route:clear');
		shell_exec('sudo php artisan route:clear');

	}

	protected function publishPkg() {

		foreach ($this->abs_pkgs as $pkg) {
			dump('Pulishing ' . $pkg);
			shell_exec('sudo php artisan vendor:publish --force --provider=' . $pkg);
		}

	}
	public function run() {
		// $this->commonCommands();

	}
}
