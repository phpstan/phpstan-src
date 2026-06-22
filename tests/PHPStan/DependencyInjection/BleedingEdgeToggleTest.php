<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Generator;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;

class BleedingEdgeToggleTest extends PHPStanTestCase
{

	public function testWithBleedingEdgeRunsCallbackWithToggleSet(): void
	{
		$backup = BleedingEdgeToggle::isBleedingEdge();
		try {
			BleedingEdgeToggle::setBleedingEdge(false);

			$observed = BleedingEdgeToggle::withBleedingEdge(true, static fn (): bool => BleedingEdgeToggle::isBleedingEdge());

			$this->assertTrue($observed);
		} finally {
			BleedingEdgeToggle::setBleedingEdge($backup);
		}
	}

	public function testWithBleedingEdgeRestoresPreviousValue(): void
	{
		$backup = BleedingEdgeToggle::isBleedingEdge();
		try {
			BleedingEdgeToggle::setBleedingEdge(false);

			BleedingEdgeToggle::withBleedingEdge(true, static fn (): bool => true);

			$this->assertFalse(BleedingEdgeToggle::isBleedingEdge());
		} finally {
			BleedingEdgeToggle::setBleedingEdge($backup);
		}
	}

	public function testWithBleedingEdgeRejectsGeneratorCallbackAndRestoresValue(): void
	{
		$backup = BleedingEdgeToggle::isBleedingEdge();
		try {
			BleedingEdgeToggle::setBleedingEdge(false);

			$generatorCallback = static function (): Generator {
				yield BleedingEdgeToggle::isBleedingEdge();
			};

			try {
				BleedingEdgeToggle::withBleedingEdge(true, $generatorCallback);
				$this->fail('Expected ShouldNotHappenException was not thrown.');
			} catch (ShouldNotHappenException $e) {
				// A generator callback would hold the toggle across a `yield` and leak it
				// into unrelated tests - it must be rejected eagerly.
				$this->assertStringContainsString('not allowed to yield', $e->getMessage());
			}

			// The toggle must be restored even when the callback is rejected.
			$this->assertFalse(BleedingEdgeToggle::isBleedingEdge());
		} finally {
			BleedingEdgeToggle::setBleedingEdge($backup);
		}
	}

}
