<?php
namespace Abs\HelperPkg\Traits;
use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Company;
use DB;

trait SeederTrait {

	// Static Operations --------------------------------------------------------------

	public static function saveFromExcelArray($record_data) {
		try {
			DB::beginTransaction();
			$errors = [];

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

			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}

			if (Self::$AUTO_GENERATE_CODE) {
				if (empty($record_data['Code'])) {
					$record = static::firstOrNew([
						'company_id' => $company->id,
						'name' => $record_data['Name'],
					]);
					$result = SerialNumberGroup::generateNumber(static::$SERIAL_NUMBER_CATEGORY_ID);
					if ($result['success']) {
						$record_data['Code'] = $result['number'];
					} else {
						return [
							'success' => false,
							'errors' => $result['errors'],
						];
					}
				} else {
					$record = static::firstOrNew([
						'company_id' => $company->id,
						'code' => $record_data['Code'],
					]);
				}
			} else {
				$record = static::firstOrNew([
					'company_id' => $company->id,
					'code' => $record_data['Code'],
				]);
			}
			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}
			$record->created_by_id = $created_by_id;
			$record->save();
			DB::commit();
			return [
				'success' => true,
			];

		} catch (\Exception $e) {
			DB::rollback();
			return [
				'success' => false,
				'errors' => [$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile()],
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
					// dump($status);
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
		dump($success . ' Records Success');
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
				$value = null;
				switch ($rule) {
				case 'required':
					if ($values[$columnName] === '') {
						$errors[] = $columnName . ' is empty';
						break;
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
							break;
						}
						$value = $fk->id;
					}
					break;

				case 'nullable':
					if ($values[$columnName] !== '') {
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
						break;
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
					$value = null;
					if (!empty($values[$columnName])) {
						$value = date('Y-m-d', strtotime($values[$columnName]));
					}
					break;

				case 'unsigned_integer':
					// if ($values[$columnName] === '') {
					// 	continue;
					// }
					$value = 0;
					if (!is_numeric($values[$columnName])) {
						$errors[] = $columnName . ' should be a integer number' . ' : ' . $values[$columnName];
						break;
					}
					if ($values[$columnName] < 0) {
						$errors[] = $columnName . ' should be greater than 0' . ' : ' . $values[$columnName];
						break;
					}
					$value = $values[$columnName];
					break;

				case 'unsigned_decimal':
					if ($values[$columnName] !== '') {
						break;
					}
					$value = 0;
					if (!is_numeric($values[$columnName])) {
						$errors[] = $columnName . ' should be a decimal number' . ' : ' . $values[$columnName];
						break;
					}
					if ($values[$columnName] < 0) {
						$errors[] = $columnName . ' should be greater than 0' . ' : ' . $values[$columnName];
						break;
					}
					$value = $values[$columnName];
					break;
				}
				if (isset($details['table_column_name'])) {
					$object->{$details['table_column_name']} = $value;
					// if ($columnName == 'KM Reading') {
					// 	dump($value);
					// 	dd($object);
					// }
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
