<?php

namespace RoundFamilyTestPHP8;

use function PHPStan\Testing\assertType;

// Round
assertType('float', round(123));
assertType('float', round(123.456));
assertType('*NEVER*', round('123'));
assertType('*NEVER*', round('123.456'));
assertType('*NEVER*', round(null));
assertType('*NEVER*', round(true));
assertType('*NEVER*', round(false));
assertType('*NEVER*', round(new \stdClass));
assertType('*NEVER*', round(''));
assertType('*NEVER*', round(array()));
assertType('*NEVER*', round(array(123)));
assertType('*NEVER*', round());
assertType('(*NEVER*|float)', round($_GET['foo']));

// Ceil
assertType('float', ceil(123));
assertType('float', ceil(123.456));
assertType('*NEVER*', ceil('123'));
assertType('*NEVER*', ceil('123.456'));
assertType('*NEVER*', ceil(null));
assertType('*NEVER*', ceil(true));
assertType('*NEVER*', ceil(false));
assertType('*NEVER*', ceil(new \stdClass));
assertType('*NEVER*', ceil(''));
assertType('*NEVER*', ceil(array()));
assertType('*NEVER*', ceil(array(123)));
assertType('*NEVER*', ceil());
assertType('(*NEVER*|float)', ceil($_GET['foo']));

// Floor
assertType('float', floor(123));
assertType('float', floor(123.456));
assertType('*NEVER*', floor('123'));
assertType('*NEVER*', floor('123.456'));
assertType('*NEVER*', floor(null));
assertType('*NEVER*', floor(true));
assertType('*NEVER*', floor(false));
assertType('*NEVER*', floor(new \stdClass));
assertType('*NEVER*', floor(''));
assertType('*NEVER*', floor(array()));
assertType('*NEVER*', floor(array(123)));
assertType('*NEVER*', floor());
assertType('(*NEVER*|float)', floor($_GET['foo']));
