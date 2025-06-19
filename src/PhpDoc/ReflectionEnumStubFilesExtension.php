<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;

#[AutowiredService]
final class ReflectionEnumStubFilesExtension implements StubFilesExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getFiles(): array
	{
		if (!$this->phpVersion->supportsEnums()) {
			return [];
		}

		if (!$this->phpVersion->supportsLazyObjects()) {
			return [__DIR__ . '/../../stubs/ReflectionEnum.stub'];
		}

		return [__DIR__ . '/../../stubs/ReflectionEnumWithLazyObjects.stub'];
	}

}
