<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;

#[AutowiredService]
final class ReflectionClassStubFilesExtension implements StubFilesExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getFiles(): array
	{
		if (!$this->phpVersion->supportsLazyObjects()) {
			return [
				__DIR__ . '/../../stubs/ReflectionClass.stub',
				__DIR__ . '/../../stubs/ReflectionObject.stub',
			];
		}

		return [
			__DIR__ . '/../../stubs/ReflectionClassWithLazyObjects.stub',
			__DIR__ . '/../../stubs/ReflectionObjectWithLazyObjects.stub',
		];
	}

}
