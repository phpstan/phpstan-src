<?php declare(strict_types = 1);

namespace Bug523;

use function get_class;
use function PHPStan\Testing\assertType;

class Base {}
class Child1 extends Base {}

function typeHintedFunction(Child1 $object)
{
}

function testPasses(Base $object)
{
	switch (get_class($object)) {
		case Child1::class:
			assertType('Bug523\Child1', $object);
			typeHintedFunction($object);
	}

	$type = get_class($object);
	switch ($type) {
		case Child1::class:
			assertType('Bug523\Child1', $object);
			typeHintedFunction($object);
	}
}
