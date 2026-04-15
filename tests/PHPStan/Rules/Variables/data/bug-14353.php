<?php

namespace Bug14353;

/** @return array<int> */
function get(): array
{
	return [1];
}

class Test
{
	public function test(): void
	{
		$reports = [];

		foreach (get() as $report) {
			$reports[$report] = $report;
		}

		if (isset($this->data)) {
			foreach ($reports as $report_id => $report) {
				$report_ids[$report_id] = 1;
			}
		} else {
			foreach ($reports as $report_id => $report) {
				$report_ids[$report_id] = 1;
			}
		}

		if (isset($report_ids)) {
			var_dump($report_ids);

			foreach ($reports as $report) {}

			var_dump($report_ids);
		}
	}

	public function testWithIfBranch(): void
	{
		$reports = [];

		foreach (get() as $report) {
			$reports[$report] = $report;
		}

		if (isset($this->data)) {
			foreach ($reports as $report_id => $report) {
				$report_ids[$report_id] = 1;
			}
		} else {
			foreach ($reports as $report_id => $report) {
				$report_ids[$report_id] = 1;
			}
		}

		if (isset($report_ids)) {
			var_dump($report_ids);

			if ($reports === []) {
				echo 'empty';
			}

			var_dump($report_ids);
		}
	}
}
