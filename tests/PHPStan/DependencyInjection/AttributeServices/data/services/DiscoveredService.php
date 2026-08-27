<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

#[AutowiredService]
final class DiscoveredService implements ReadWritePropertiesExtension
{

	public function __construct(
		#[AutowiredParameter]
		private string $currentWorkingDirectory,
		#[AutowiredParameter(ref: '%tmpDir%')]
		private string $tmpDir,
	)
	{
	}

	public function getCurrentWorkingDirectory(): string
	{
		return $this->currentWorkingDirectory;
	}

	public function getTmpDir(): string
	{
		return $this->tmpDir;
	}

	public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	public function isInitialized(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

}
