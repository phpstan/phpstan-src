<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use Override;
use PHPStan\DependencyInjection\BleedingEdgeToggle;

/**
 * The global state a container installs - here the bleeding edge toggle, which
 * ConstantArrayType reads in its constructor - has to belong to the test class
 * that is running, not to whichever class ran before it in the same ParaTest
 * worker process. On PHPUnit >= 10 InitContainerBeforeTestSubscriber guarantees
 * that; PHPUnit 9 rejects that extension, so PHPStanTestCase::setUp() does.
 */
class BleedingEdgeContainerStateTest extends PHPStanTestCase
{

	#[Override]
	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
		];
	}

	public function testThisClassesContainerOwnsTheGlobalState(): void
	{
		$this->assertTrue(BleedingEdgeToggle::isBleedingEdge());
	}

}
