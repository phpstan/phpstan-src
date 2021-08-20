<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Broker\Broker;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;

class UniversalObjectCratesClassReflectionExtensionTest extends \PHPStan\Testing\BaseTestCase
{

	public function testNonexistentClass(): void
	{
		$broker = self::getContainer()->getByType(Broker::class);
		$extension = new UniversalObjectCratesClassReflectionExtension([
			'NonexistentClass',
			'stdClass',
		]);
		$extension->setBroker($broker);
		$this->assertTrue($extension->hasProperty($broker->getClass(\stdClass::class), 'foo'));
	}

	public function testDifferentGetSetType(): void
	{
		require_once __DIR__ . '/data/universal-object-crates.php';

		$broker = self::getContainer()->getByType(Broker::class);
		$extension = new UniversalObjectCratesClassReflectionExtension([
			'UniversalObjectCreates\DifferentGetSetTypes',
		]);
		$extension->setBroker($broker);

		$this->assertEquals(
			new ObjectType('UniversalObjectCreates\DifferentGetSetTypesValue'),
			$extension
				->getProperty($broker->getClass('UniversalObjectCreates\DifferentGetSetTypes'), 'foo')
				->getReadableType()
		);
		$this->assertEquals(
			new StringType(),
			$extension
				->getProperty($broker->getClass('UniversalObjectCreates\DifferentGetSetTypes'), 'foo')
				->getWritableType()
		);
	}

}
