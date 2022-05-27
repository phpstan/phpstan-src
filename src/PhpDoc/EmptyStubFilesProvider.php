<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

class EmptyStubFilesProvider implements StubFilesProvider
{

	public function getStubFiles(): array
	{
		return [];
	}

}
