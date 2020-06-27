<?php
namespace Abs\HelperPkg\Traits;
use App\Company;
trait SeederTrait {

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

			$record = static::firstOrNew([
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

				$status = static::saveFromObject($record_data, $company);
				if (!$status['success']) {
					$error_records[] = array_merge($record_data->toArray(), [
						'Record No' => $key + 1,
						'Errors' => implode(',', $status['errors']),
					]);
					continue;
				}
				$success++;
			} catch (Exception $e) {
				dump($e);
			}
		}
		dump($success . ' Records Processed');
		dump(count($error_records) . ' Errors');
		dump($error_records);
		return $error_records;
	}

	public static function createMultipleFromArrays($records) {
		foreach ($records as $id => $detail) {
			$record = static::firstOrNew([
				'id' => $id,
			]);
			$record->fill($detail['data']);
			$record->save();
		}
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select') {
		$list = Collect(static::select([
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

	public static function validateAndFillExcelColumns($values, $excelColumns, &$object) {
		$errors = [];
		foreach ($excelColumns as $columnName => $details) {
			foreach ($details['rules'] as $rule => $ruleDetails) {
				switch ($rule) {
				case 'required':
					if (empty($values[$columnName])) {
						$errors[] = $columnName . ' is empty';
						continue;
					}
					$value = $values[$columnName];
					break;

				case 'fk':
					if (!empty($values[$columnName])) {
						$fk = $ruleDetails['class']::where([
							$ruleDetails['foreign_table_column'] => $values[$columnName],
						])->first();
						if (!$fk) {
							$errors[] = 'Invalid ' . $columnName . ' : ' . $values[$columnName];
							continue;
						}
						$value = $fk->id;
					}
					break;

				case 'nullable':
					if (!empty($values[$columnName])) {
						$value = $values[$columnName];
					} else {
						$value = null;
					}
					break;

				case 'email':
					if (!empty($values[$columnName])) {
						$value = $values[$columnName];
					}
					break;

				case 'mobile_number':
					if (!empty($values[$columnName]) && strlen($values[$columnName]) != 10) {
						$errors[] = $columnName . ' Length should be 10' . ' : ' . $values[$columnName];
						continue;
					} else {
						$value = $values[$columnName];
					}
					break;

				case 'boolean':
					if ($values[$columnName] == 'Yes') {
						$value = 1;
					} else {
						$value = 0;
					}
					break;

				case 'date':
					if (!empty($values[$columnName])) {
						$value = date('Y-m-d', strtotime($values[$columnName]));
					}
					break;
				}
				if (isset($details['table_column_name'])) {
					$object->{$details['table_column_name']} = $value;
				} else {
					$column = snake_case($columnName);
					$object->{$column} = $value;
				}
				$value = null;
			}
		}

		if (count($errors) > 0) {
			return [
				'success' => false,
				'errors' => $errors,
			];
		}
		return [
			'success' => true,
			'record' => $object,
		];
	}

}
