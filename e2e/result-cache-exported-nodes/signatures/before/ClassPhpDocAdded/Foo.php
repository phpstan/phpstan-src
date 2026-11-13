<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassPhpDocAdded;

class Foo
{

	/**
	 * @param list<mixed> $arguments
	 */
	public function __call(string $name, array $arguments): mixed
	{
		return null;
	}

}
