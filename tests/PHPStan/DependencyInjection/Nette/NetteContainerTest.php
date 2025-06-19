<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Nette;

use PHPStan\DependencyInjection\MissingServiceException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension;

class NetteContainerTest extends PHPStanTestCase
{

	public function testGetServiceThrows(): void
	{
		$container = self::getContainer();

		$this->expectException(MissingServiceException::class);
		$container->getService('nonexistent');
	}

	public function testGetByTypeNotFoundThrows(): void
	{
		$container = self::getContainer();

		$this->expectException(MissingServiceException::class);
		$container->getByType(TrinaryLogic::class);
	}

	public function testGetByTypeNotUniqueThrows(): void
	{
		$container = self::getContainer();

		$this->expectException(MissingServiceException::class);
		$container->getByType(ReflectionGetAttributesMethodReturnTypeExtension::class);
	}

}
