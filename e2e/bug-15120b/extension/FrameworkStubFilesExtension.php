<?php declare(strict_types = 1);

namespace Bug15120b;

use PHPStan\PhpDoc\StubFilesExtension;
use function constant;
use function version_compare;

final class FrameworkStubFilesExtension implements StubFilesExtension
{

	public function getFiles(): array
	{
		// mimics larastan: the constant is defined by a bootstrapFiles entry
		if (version_compare((string) constant('Bug15120b\FRAMEWORK_VERSION'), '10.0', '<')) {
			return [];
		}

		return [__DIR__ . '/../stubs/Foo.stub'];
	}

}
