<?php
namespace Abs\HelperPkg\Traits;
use Auth;
trait SeederTrait {
	public static function createFromCollection($records, $company = null, $specific_company = null, $tc, $command = null) {

		$success = 0;
		$error_records = [];
		foreach ($records as $key => $record_data) {
			try {
				if (!$record_data->company_code) {
					continue;
				}

				if ($specific_company) {
					if ($record_data->company_code != $specific_company->code) {
						continue;
					}
				}

				if ($tc) {
					if ($record_data->tc != $tc) {
						continue;
					}
				}

				$status = self::createFromObject($record_data, $company);
				if (!$status['success']) {
					$error_records[] = array_merge($record_data->toArray(), [
						'Record No' => $key + 1,
						'Errors' => implode(',', $status['errors']),
					]);
				}
				$success++;
			} catch (Exception $e) {
				dd($e);
			}
		}
		return $error_records;
		dump($success . ' Records Processed');
	}

	public static function createMultipleFromArrays($records) {
		foreach ($records as $id => $detail) {
			$record = self::firstOrNew([
				'id' => $id,
			]);
			$record->fill($detail['data']);
			$record->save();
		}
	}

	public function scopeCompany($query, $table_name = null) {
		if ($table_name) {
			$table_name .= '.';
		} else {
			$table_name = '';
		}
		return $query->where($table_name . 'company_id', Auth::user()->company_id);
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select') {
		$list = Collect(Self::select([
			'id',
			'name',
		])
				->orderBy('name')
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

}
