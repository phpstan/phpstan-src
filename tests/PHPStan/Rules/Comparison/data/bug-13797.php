<?php declare(strict_types = 1);

namespace Bug13797;

/** @return ?array<string, mixed> */
function alert(): ?array
{
	$alerts = [];

	if (rand()) {
		$alerts[] = [
			'message'  => "Foo",
			'details'  => "bar",
			'duration' => rand() ?: null,
			'severity' => 100,
		];
	}

	if (rand()) {
		$alerts[] = [
			'message'  => 'Offline',
			'duration' => rand() ?: null,
			'severity' => 99,
		];
	}

	if (rand()) {
		$alerts[] = [
			'message'  => 'Running W/O Operator',
			'duration' => rand() ?: null,
			'severity' => 75,
		];
	}

	if (rand()) {
		$alerts[] = [
			'message'  => 'No Queue',
			'duration' => rand() ?: null,
			'severity' => 60,
		];
	}

	if (rand()) {
		if (rand()) {
			$alerts[] = [
				'message'  => 'Not Scheduled',
				'duration' => null,
				'severity' => 25,
			];
		}

		if (rand()) {
			$alerts[] = [
				'message'  => 'On Lunch',
				'duration' => rand() ?: null,
				'severity' => 24,
			];
		}

		if (rand()) {
			$alerts[] = [
				'message'  => 'On Break',
				'duration' => rand() ?: null,
				'severity' => 24,
			];
		}
	}

	if (rand()) {
		$alerts[] = [
			'message'  => 'Idle',
			'duration' => rand() ?: null,
			'severity' => 23,
		];
	}

	if (!$alerts) {
		return null;
	}

	return $alerts[0];
}
