<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Testing\PHPStanTestCase;

class SkipCheckGenericClassesExtensionTest extends PHPStanTestCase
{

	public function testClassesMarkedInStubsAreSkipped(): void
	{
		$featureToggles = self::getContainer()->getParameter('featureToggles');
		$this->assertSame([
			'DOMNamedNodeMap',
			'ParentIterator',
			'RecursiveCachingIterator',
			'RecursiveFilterIterator',
			'RecursiveRegexIterator',
			'ReflectionObject',
		], $featureToggles['skipCheckGenericClasses']);
	}

}
