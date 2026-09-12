<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\DependencyInjection\BleedingEdgeToggle;

/**
 * @see BleedingEdgeContainerStateTest for the same guarantee with a non-base container
 */
class ContainerStateTest extends PHPStanTestCase
{

	public function testThisClassesContainerOwnsTheGlobalState(): void
	{
		$this->assertFalse(BleedingEdgeToggle::isBleedingEdge());
	}

}
