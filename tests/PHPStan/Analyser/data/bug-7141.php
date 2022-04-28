<?php

namespace Bug7141;

use function PHPStan\Testing\assertType;

interface Container
{
	/**
	 * @template T
	 * @param string|class-string<T> $id
	 * @return ($id is class-string ? T : mixed)
	 */
	public function get(string $id): mixed;
}


function (Container $c) {
	assertType('mixed', $c->get('test'));
	assertType('stdClass', $c->get(\stdClass::class));
};
