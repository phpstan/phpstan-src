<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Php\PhpVersion;

class JsonValidateStubFilesExtension implements StubFilesExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getFiles(): array
	{
		if (!$this->phpVersion->supportsJsonValidate()) {
			return [];
		}

		return [__DIR__ . '/../../stubs/json_validate.stub'];
	}

}
