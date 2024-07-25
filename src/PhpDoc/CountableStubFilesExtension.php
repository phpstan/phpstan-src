<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

final class CountableStubFilesExtension implements StubFilesExtension
{

	public function __construct(private bool $bleedingEdge)
	{
	}

	public function getFiles(): array
	{
		if ($this->bleedingEdge) {
			return [__DIR__ . '/../../stubs/bleedingEdge/Countable.stub'];
		}

		return [__DIR__ . '/../../stubs/Countable.stub'];
	}

}
