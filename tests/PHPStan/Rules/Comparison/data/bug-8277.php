<?php declare(strict_types = 1);

namespace Bug8277;

use Generator;
use function PHPStan\Testing\assertType;

/**
 * {@see FunctionalStreamTest::testWindow()}
 *
 * @template K
 * @template T
 *
 * @param iterable<K, T> $stream
 * @param positive-int $width
 *
 * @return Generator<int, T[]>
 */
function swindow(iterable $stream, int $width): Generator
{
	$window = [];
	foreach ($stream as $value) {
		$window[] = $value;
		$count = count($window);

		assertType('int<1, max>', $count);

		switch (true) {
			case $count > $width:
				array_shift($window);
			// no break
			case $count === $width:
				yield $window;
		}
	}
}
