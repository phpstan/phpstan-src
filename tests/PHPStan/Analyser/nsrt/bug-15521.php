<?php declare(strict_types = 1);

namespace Bug15521;

use function PHPStan\Testing\assertType;

interface Search
{

	public function isEmpty(): bool;

}

interface Factory
{

	/** @return array{Search, bool} */
	public function create(string $filter, bool $changeStep): array;

}

/**
 * @param list<string> $steps
 */
function foo(array $steps, Factory $factory): void
{
	$resultData = ['a' => 1];
	$changeStep = false;

	foreach ($steps as $step => $filter) {
		[$search, $emptyResultAllowed] = $factory->create($filter, $changeStep);

		if ($changeStep) {
			if (!$search->isEmpty()) {
				$resultData['changeStep'] = $step;

				break;
			}

			continue;
		}

		if ($emptyResultAllowed || !$search->isEmpty()) {
			$resultData = ['a' => 2];

			break;
		}

		$changeStep = true;
	}

	assertType('array{a: 1, changeStep?: int<0, max>}|array{a: 2}', $resultData);
}

/**
 * @param list<string> $steps
 */
function whileLoop(array $steps, Factory $factory): void
{
	$resultData = ['a' => 1];
	$changeStep = false;
	$step = 0;

	while ($step < count($steps)) {
		[$search, $emptyResultAllowed] = $factory->create($steps[$step], $changeStep);

		if ($changeStep) {
			if (!$search->isEmpty()) {
				$resultData['changeStep'] = $step;

				break;
			}

			$step++;
			continue;
		}

		if ($emptyResultAllowed || !$search->isEmpty()) {
			$resultData = ['a' => 2];

			break;
		}

		$changeStep = true;
	}

	assertType('array{a: 1, changeStep?: int<0, max>}|array{a: 2}', $resultData);
}

/**
 * @param list<string> $steps
 */
function forLoop(array $steps, Factory $factory): void
{
	$resultData = ['a' => 1];
	$changeStep = false;

	for ($step = 0; $step < count($steps); $step++) {
		[$search, $emptyResultAllowed] = $factory->create($steps[$step], $changeStep);

		if ($changeStep) {
			if (!$search->isEmpty()) {
				$resultData['changeStep'] = $step;

				break;
			}

			continue;
		}

		if ($emptyResultAllowed || !$search->isEmpty()) {
			$resultData = ['a' => 2];

			break;
		}

		$changeStep = true;
	}

	assertType('array{a: 1, changeStep?: int<1, max>}|array{a: 2}', $resultData);
}

/**
 * @param list<string> $steps
 */
function doWhileLoop(array $steps, Factory $factory): void
{
	$resultData = ['a' => 1];
	$changeStep = false;
	$step = 0;

	do {
		[$search, $emptyResultAllowed] = $factory->create($steps[$step], $changeStep);

		if ($changeStep) {
			if (!$search->isEmpty()) {
				$resultData['changeStep'] = $step;

				break;
			}

			$step++;
			continue;
		}

		if ($emptyResultAllowed || !$search->isEmpty()) {
			$resultData = ['a' => 2];

			break;
		}

		$changeStep = true;
	} while ($step < count($steps));

	assertType('array{a: 1, changeStep?: int<0, max>}|array{a: 2}', $resultData);
}

/**
 * @param list<string> $steps
 */
function throwPoints(array $steps, Factory $factory): void
{
	$resultData = 1;
	$changeStep = false;

	try {
		foreach ($steps as $step => $filter) {
			[$search, $emptyResultAllowed] = $factory->create($filter, $changeStep);

			if ($changeStep) {
				if (!$search->isEmpty()) {
					$resultData = 3;
					throw new \RuntimeException();
				}

				continue;
			}

			if ($emptyResultAllowed || !$search->isEmpty()) {
				$resultData = 2;

				break;
			}

			$changeStep = true;
		}
	} catch (\RuntimeException $e) {
		assertType('1|3', $resultData);
	}
}
