<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Php\PhpVersion;

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

		return [__DIR__ . '/../../stubs/ReflectionEnum.stub'];
	}

}
