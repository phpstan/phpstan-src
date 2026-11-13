<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\EnumPhpDocChanged;

/**
 * @method string magic()
 */
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
