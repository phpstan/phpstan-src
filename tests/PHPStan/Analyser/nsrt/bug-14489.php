<?php declare(strict_types = 1);

namespace Bug14489;

use function array_key_exists;
use function array_merge;
use function array_unique;
use function array_values;
use function PHPStan\Testing\assertType;

function () {
	$data = [['c1' => [1], 'c2' => [4]]];

	$cData = [];
	foreach ($data as $cMap) {
		foreach ($cMap as $c => $ids) {
			if (array_key_exists($c, $cData)) {
				$cData[$c] = array_unique(array_merge($cData[$c], $ids));
			} else {
				$cData[$c] = $ids;
			}
		}
	}

	$values = array_values($cData);
	assertType('array{array{1}, array{4}}', $values);
};

function () {
	/** @var 'c1'|'c2' $c */
	$c = 'c1';
	/** @var array{1}|array{4} $ids */
	$ids = [1];

	$cData = [];
	while (rand(0, 1)) {
		if (array_key_exists($c, $cData)) {
			assertType('non-empty-array{c1?: array{1}|array{4}, c2?: array{1}|array{4}}', $cData);
			assertType('array{1}|array{4}', $cData[$c]);
			$cData[$c] = $cData[$c];
		} else {
			$cData[$c] = $ids;
		}
	}
	assertType('array{}|array{c1?: array{1}|array{4}, c2?: array{1}|array{4}}', $cData);
};

/**
 * @param list<array<'c1'|'c2', array{1}|array{4}>> $data
 */
function (array $data) {
	$cData = [];
	foreach ($data as $cMap) {
		foreach ($cMap as $c => $ids) {
			if (array_key_exists($c, $cData)) {
				$cData[$c] = array_unique(array_merge($cData[$c], $ids));
			} else {
				$cData[$c] = $ids;
			}
		}
	}

	$values = array_values($cData);
	assertType('list', $values);
};
