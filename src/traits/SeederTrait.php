<?php
namespace Abs\HelperPkg\Traits;
use App\Company;
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

				$status = self::saveFromObject($record_data, $company);
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

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function ($query) use ($term) {
				$query->orWhere('code', 'LIKE', '%' . $term . '%');
				$query->orWhere('name', 'LIKE', '%' . $term . '%');
			});
		}
	}

	// Static Operations --------------------------------------------------------------

	public static function saveFromExcelArray($record_data) {
		try {
			$errors = [];

			// $validation = Self::validate($original_record, $admin);
			// if (count($validation['success']) > 0 || count($errors) > 0) {
			// 	return [
			// 		'success' => false,
			// 		'errors' => array_merge($validation['errors'], $errors),
			// 	];
			// }

			$company = Company::where('code', $record_data['Company Code'])->first();
			if (!$company) {
				return [
					'success' => false,
					'errors' => ['Invalid Company : ' . $record_data['Company Code']],
				];
			}

			if (!isset($record_data['created_by_id'])) {
				$admin = $company->admin();

				if (!$admin) {
					return [
						'success' => false,
						'errors' => ['Default Admin user not found'],
					];
				}
				$created_by_id = $admin->id;
			} else {
				$created_by_id = $record_data['created_by_id'];
			}

			if (empty($record_data['Code'])) {
				$errors[] = 'Code is empty';
			}

			if (empty($record_data['Name'])) {
				$errors[] = 'Name is empty';
			}

			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}

			$record = Self::firstOrNew([
				'company_id' => $company->id,
				'code' => $record_data['Code'],
			]);

			$record->name = $record_data['Name'];
			$record->created_by_id = $created_by_id;
			$record->save();
			return [
				'success' => true,
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'errors' => [$e->getMessage()],
			];
		}
	}

	public static function importFromJob($job) {

		try {
			$response = ImportCronJob::getRecordsFromExcel($job, 'N');
			$rows = $response['rows'];
			$header = $response['header'];

			$all_error_records = [];
			foreach ($rows as $k => $row) {
				$record = [];
				foreach ($header as $key => $column) {
					if (!$column) {
						continue;
					} else {
						$record[$column] = trim($row[$key]);
					}
				}
				$original_record = $record;
				$record['Company Code'] = $job->company->code;
				$record['created_by_id'] = $job->created_by_id;
				$result = self::saveFromExcelArray($record);
				if (!$result['success']) {
					$original_record['Record No'] = $k + 1;
					$original_record['Error Details'] = implode(',', $result['errors']);
					$all_error_records[] = $original_record;
					$job->incrementError();
					continue;
				}

				$job->incrementNew();

				DB::commit();
				//UPDATING PROGRESS FOR EVERY FIVE RECORDS
				if (($k + 1) % 5 == 0) {
					$job->save();
				}
			}

			//COMPLETED or completed with errors
			$job->status_id = $job->error_count == 0 ? 7202 : 7205;
			$job->save();

			ImportCronJob::generateImportReport([
				'job' => $job,
				'all_error_records' => $all_error_records,
			]);

		} catch (\Throwable $e) {
			$job->status_id = 7203; //Error
			$job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
			$job->save();
			dump($job->error_details);
		}

	}

	public static function saveFromObject($record_data) {

		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
		];
		return self::saveFromExcelArray($record);
	}
}
