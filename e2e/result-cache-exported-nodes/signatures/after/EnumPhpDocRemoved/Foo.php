<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\EnumPhpDocRemoved;

enum Foo
{

	case A;

	/**
	 * @param list<mixed> $arguments
	 */
	public function __call(string $name, array $arguments): mixed
	{
		return null;
	}

}
