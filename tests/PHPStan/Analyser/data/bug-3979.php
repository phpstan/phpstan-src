<?php declare(strict_types = 1);

namespace Bug3979;

class A { }
class B extends A { }
class C { }

/**
 * @param mixed $value
 * @param string $class_type
 */
function check_class($value, $class_type): bool
{
	if (!is_string($value) || !class_exists($value) ||
		($class_type && !is_subclass_of($value, $class_type)))
		return false;
	return true;
}

var_dump(check_class("B", "A")); // true
var_dump(check_class("C", "A")); // false
var_dump(check_class("B", A::class)); // true
var_dump(check_class("C", A::class)); // false
var_dump(check_class(B::class, A::class)); // true
var_dump(check_class(C::class, A::class)); // false
