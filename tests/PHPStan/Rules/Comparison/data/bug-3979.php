<?php

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

/**
 * @param class-string $value
 * @param string $class_type
 */
function check_class2($value, $class_type): bool
{
	if (is_a($value, $class_type, true)) {
		return true;
	}
	return false;
}

/**
 * @param class-string|object $value
 * @param string $class_type
 */
function check_class3($value, $class_type): bool
{
	if (is_a($value, $class_type, true)) {
		return true;
	}
	return false;
}

/**
 * @param class-string|object $value
 * @param string $class_type
 */
function check_class4($value, $class_type): bool
{
	if (is_subclass_of($value, $class_type, true)) {
		return true;
	}
	return false;
}

/**
 * @param object $value
 * @param string $class_type
 */
function check_class5($value, $class_type): bool
{
	if (is_a($value, $class_type, true)) {
		return true;
	}
	return false;
}

/**
 * @param object $value
 * @param string $class_type
 */
function check_class6($value, $class_type): bool
{
	if (is_subclass_of($value, $class_type, true)) {
		return true;
	}
	return false;
}

/**
 * @param object $value
 * @param class-string $class_type
 */
function check_class7($value, $class_type): bool
{
	if (is_a($value, $class_type, true)) {
		return true;
	}
	return false;
}

/**
 * @param object $value
 * @param class-string $class_type
 */
function check_class8($value, $class_type): bool
{
	if (is_subclass_of($value, $class_type, true)) {
		return true;
	}
	return false;
}

/**
 * @param class-string $value
 * @param class-string $class_type
 */
function check_class9($value, $class_type): bool
{
	if (is_a($value, $class_type, true)) {
		return true;
	}
	return false;
}

/**
 * @param class-string $value
 * @param class-string $class_type
 */
function check_class10($value, $class_type): bool
{
	if (is_subclass_of($value, $class_type, true)) {
		return true;
	}
	return false;
}
