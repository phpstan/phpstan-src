<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use function dirname;

class StubFilesExtensionLoader implements StubFilesExtension
{

	public function __construct(
		private bool $bleedingEdge,
	)
	{
	}

	public function getFiles(): array
	{
		$path = dirname(__DIR__, 2) . '/stubs';

		if ($this->bleedingEdge === true) {
			$path .= '/bleedingEdge';

			return [
				$path . '/Countable.stub',
			];
		}

		return [];
	}

}
