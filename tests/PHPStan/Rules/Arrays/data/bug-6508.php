<?php

namespace Bug6508;

class Foo
{
	/**
	 * @param array{
	 *   type1?: array{bool: bool},
	 *   type2?: array{bool: bool}
	 * } $types
	 * @param 'type1'|'type2' $type
	 */
	function test(array $types, string $type): void
	{
		if (isset($types[$type]) && $types[$type]['bool']) {}
	}
}
