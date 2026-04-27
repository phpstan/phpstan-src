<?php declare(strict_types = 1);

namespace Bug13509;

use function PHPStan\Testing\assertType;

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

	if ($alerts === []) {
		return null;
	}

	assertType("non-empty-list<array{message: 'Foo', details: 'bar', duration: int<1, max>|null, severity: 100}|array{message: 'Idle', duration: int<1, max>|null, severity: 23}|array{message: 'No Queue', duration: int<1, max>|null, severity: 60}|array{message: 'Not Scheduled', duration: null, severity: 25}|array{message: 'Offline', duration: int<1, max>|null, severity: 99}|array{message: 'On Break'|'On Lunch', duration: int<1, max>|null, severity: 24}|array{message: 'Running W/O Operator', duration: int<1, max>|null, severity: 75}>", $alerts);

	usort($alerts, fn ($a, $b) => $b['severity'] <=> $a['severity']);

	return $alerts[0];
}
