<?php
namespace Abs\HelperPkg\Traits;

trait PermissionTrait {
	public static function createFromArrays($permissions) {
		foreach ($permissions as $permission_id => $data) {
			if (isset($data['operation']) && $data['operation'] == 'delete') {
				$permission = self::where([
					'name' => $data['name'],
				])->first();
				if ($permission) {
					$permission->forceDelete();
					continue;
				}

			}

			$parent_id = null;

			if ($data['parent']) {
				$parent = self::where('name', $data['parent'])->first();
				if (!$parent) {
					// dump('Parent permission not found : ', $data['parent']);
					// dump($data);
					$parent_id = null;
				} else {
					$parent_id = $parent->id;
				}
			}
			$permission = self::firstOrNew([
				'name' => $data['name'],
			]);
			// $permission->fill()
			$permission->parent_id = $parent_id;
			$permission->display_order = $data['display_order'];
			$permission->display_name = $data['display_name'];
			$permission->save();
		}

	}
}
