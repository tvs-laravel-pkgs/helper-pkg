<?php
namespace Abs\HelperPkg\Traits;

trait SeederTrait {
	public static function createFromCollection($records, $company = null, $specific_company = null, $tc, $command) {
		$bar = $command->getOutput()->createProgressBar(count($records));
		$bar->start();
		$command->getOutput()->writeln(1);

		$success = 0;
		foreach ($records as $key => $record_data) {
			try {
				$bar->advance(1);
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

				$record = self::createFromObject($record_data, $company);
				$success++;
			} catch (Exception $e) {
				dd($e);
			}
		}
		$bar->finish();
		$command->getOutput()->writeln(1);
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

}
