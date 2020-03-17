<?php

namespace NewOffsetStub;

/**
 * @phpstan-implements \ArrayAccess<int, \stdClass>
 */
abstract class Foo implements \ArrayAccess
{

}

function (Foo $foo): void {
	$foo[] = new \stdClass();
};
