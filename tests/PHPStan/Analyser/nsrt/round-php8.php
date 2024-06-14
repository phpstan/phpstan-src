<?php // lint >= 8.0

namespace RoundFamilyTestPHP8;

use function PHPStan\Testing\assertType;

$maybeNull = null;
if (rand(0, 1)) {
	$maybeNull = 1.0;
}

// Round
assertType('float', round(123));
assertType('float', round(123.456));
assertType('float', round($_GET['foo'] / 60));
assertType('*NEVER*', round('123'));
assertType('*NEVER*', round('123.456'));
assertType('*NEVER*', round(null));
assertType('float', round($maybeNull));
assertType('*NEVER*', round(true));
assertType('*NEVER*', round(false));
assertType('*NEVER*', round(new \stdClass));
assertType('*NEVER*', round(''));
assertType('*NEVER*', round(array()));
assertType('*NEVER*', round(array(123)));
assertType('*NEVER*', round());
assertType('float', round($_GET['foo']));

// Ceil
assertType('float', ceil(123));
assertType('float', ceil(123.456));
assertType('float', ceil($_GET['foo'] / 60));
assertType('*NEVER*', ceil('123'));
assertType('*NEVER*', ceil('123.456'));
assertType('*NEVER*', ceil(null));
assertType('float', ceil($maybeNull));
assertType('*NEVER*', ceil(true));
assertType('*NEVER*', ceil(false));
assertType('*NEVER*', ceil(new \stdClass));
assertType('*NEVER*', ceil(''));
assertType('*NEVER*', ceil(array()));
assertType('*NEVER*', ceil(array(123)));
assertType('*NEVER*', ceil());
assertType('float', ceil($_GET['foo']));

// Floor
assertType('float', floor(123));
assertType('float', floor(123.456));
assertType('float', floor($_GET['foo'] / 60));
assertType('*NEVER*', floor('123'));
assertType('*NEVER*', floor('123.456'));
assertType('*NEVER*', floor(null));
assertType('float', floor($maybeNull));
assertType('*NEVER*', floor(true));
assertType('*NEVER*', floor(false));
assertType('*NEVER*', floor(new \stdClass));
assertType('*NEVER*', floor(''));
assertType('*NEVER*', floor(array()));
assertType('*NEVER*', floor(array(123)));
assertType('*NEVER*', floor());
assertType('float', floor($_GET['foo']));
