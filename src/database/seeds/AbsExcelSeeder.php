<?php

use App\Company;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class AbsExcelSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		ini_set('memory_limit', -1);

		$faker = Faker::create();

		$tc = null;
		$specific_company = null;
		$company = null;

		$use_excel_company = $this->command->ask("Use company from excel y/n?", 'y');
		if ($use_excel_company == 'n') {
			$company_id = $this->command->ask("Enter company id", '1');
			$company = Company::find($company_id);
			$delete_company = $this->command->ask("Do you want to delete company", 'n');
			if ($delete_company == 'y') {
				if ($company) {
					$company->forceDelete();
				}
			}
		}
		$use_specific_tc = $this->command->ask("Run Specific Test Case y/n?", 'n');
		if ($use_specific_tc == 'y') {
			$tc = $this->command->ask("Enter Test Case Name", 'tc1');
		}

		$use_specific_company = $this->command->ask("Run Specific Company y/n?", 'n');
		if ($use_specific_company == 'y') {
			$company_id = $this->command->ask("Enter company id", '1');
			$specific_company = Company::find($company_id);
		}

		$file_name = $this->command->ask("Enter Excel File Name", 'atv');
		$excel_file_path = 'public/excel-imports/' . $file_name . '.xlsx';
		$sheets = [];
		Excel::selectSheets('Import Config')->load($excel_file_path, function ($reader) use (&$sheets) {
			$reader->limitColumns(10);
			$reader->limitRows(50);
			$records = $reader->get();
			foreach ($records as $record) {
				if (!$record->sheet_name || $record->action != 'Execute') {
					continue;
				}
				$sheets[] = [
					'sheet_name' => $record->sheet_name,
					'class_name' => $record->class_name,
					'function_name' => $record->function_name,
					'column_limit' => $record->column_limit,
					'skip' => $record->skip,
					'row_limit' => $record->row_limit,
				];
			}
		});

		foreach ($sheets as $key => $sheet_detail) {
			$sheet_name = $sheet_detail['sheet_name'];
			$this->command->getOutput()->newLine(1);
			$this->command->info($sheet_name . ' STARTED');
			$command = $this->command;
			Excel::selectSheets($sheet_name)->load($excel_file_path, function ($reader) use ($sheet_name, $sheet_detail, $company, $specific_company, $tc, $command) {
				$reader->limitColumns($sheet_detail['column_limit']);
				$reader->skipRows($sheet_detail['skip']);
				$reader->takeRows($sheet_detail['row_limit']);
				$records = $reader->get();
				call_user_func($sheet_detail['class_name'] . '::' . $sheet_detail['function_name'] . '', $records, $company, $specific_company, $tc, $command);
			});
			$command->getOutput()->newLine(1);
			$this->command->info($sheet_name . ' COMPLETED');
		}

		return;
	}
}
