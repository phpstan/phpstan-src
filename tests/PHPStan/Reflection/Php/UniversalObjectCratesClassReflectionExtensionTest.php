<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use stdClass;

class UniversalObjectCratesClassReflectionExtensionTest extends PHPStanTestCase
{

	public function testNonexistentClass(): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$extension = new UniversalObjectCratesClassReflectionExtension($reflectionProvider, [
			'NonexistentClass',
			'stdClass',
		]);
		$this->assertTrue($extension->hasProperty($reflectionProvider->getClass(stdClass::class), 'foo'));
	}

	public function testDifferentGetSetType(): void
	{
		require_once __DIR__ . '/data/universal-object-crates.php';

		$reflectionProvider = $this->createReflectionProvider();
		$extension = new UniversalObjectCratesClassReflectionExtension($reflectionProvider, [
			'UniversalObjectCreates\DifferentGetSetTypes',
		]);

		$this->assertEquals(
			new ObjectType('UniversalObjectCreates\DifferentGetSetTypesValue'),
			$extension
				->getProperty($reflectionProvider->getClass('UniversalObjectCreates\DifferentGetSetTypes'), 'foo')
				->getReadableType(),
		);
		$this->assertEquals(
			new StringType(),
			$extension
				->getProperty($reflectionProvider->getClass('UniversalObjectCreates\DifferentGetSetTypes'), 'foo')
				->getWritableType(),
		);
	}

}
