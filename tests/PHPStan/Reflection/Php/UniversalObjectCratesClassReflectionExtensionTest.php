<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use stdClass;

class UniversalObjectCratesClassReflectionExtensionTest extends PHPStanTestCase
{

	public function testNonexistentClass(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$extension = new UniversalObjectCratesClassReflectionExtension(
			$reflectionProvider,
			['NonexistentClass', 'stdClass'],
			new AnnotationsPropertiesClassReflectionExtension(),
		);
		$this->assertTrue($extension->hasProperty($reflectionProvider->getClass(stdClass::class), 'foo'));
	}

	public function testDifferentGetSetType(): void
	{
		require_once __DIR__ . '/data/universal-object-crates.php';

		$reflectionProvider = self::createReflectionProvider();
		$extension = new UniversalObjectCratesClassReflectionExtension(
			$reflectionProvider,
			['UniversalObjectCreates\DifferentGetSetTypes'],
			new AnnotationsPropertiesClassReflectionExtension(),
		);

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

	public function testAnnotationOverrides(): void
	{
		require_once __DIR__ . '/data/universal-object-crates-annotations.php';
		$className = 'UniversalObjectCratesAnnotations\Model';

		$reflectionProvider = self::createReflectionProvider();
		$extension = new UniversalObjectCratesClassReflectionExtension(
			$reflectionProvider,
			[$className],
			new AnnotationsPropertiesClassReflectionExtension(),
		);

		$this->assertEquals(
			new StringType(),
			$extension
				->getProperty($reflectionProvider->getClass($className), 'foo')
				->getReadableType(),
		);
		$this->assertEquals(
			new StringType(),
			$extension
				->getProperty($reflectionProvider->getClass($className), 'foo')
				->getWritableType(),
		);
	}

}
