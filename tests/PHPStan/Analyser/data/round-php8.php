<?php

namespace RoundFamilyTestPHP8;

use function PHPStan\Testing\assertType;

// Round
assertType('float', round(123));
assertType('float', round(123.456));
assertType('never', round('123'));
assertType('never', round('123.456'));
assertType('never', round(null));
assertType('never', round(true));
assertType('never', round(false));
assertType('never', round(new \stdClass));
assertType('never', round(''));
assertType('never', round(array()));
assertType('never', round(array(123)));

// Ceil
assertType('float', ceil(123));
assertType('float', ceil(123.456));
assertType('never', ceil('123'));
assertType('never', ceil('123.456'));
assertType('never', ceil(null));
assertType('never', ceil(true));
assertType('never', ceil(false));
assertType('never', ceil(new \stdClass));
assertType('never', ceil(''));
assertType('never', ceil(array()));
assertType('never', ceil(array(123)));

// Floor
assertType('float', floor(123));
assertType('float', floor(123.456));
assertType('never', floor('123'));
assertType('never', floor('123.456'));
assertType('never', floor(null));
assertType('never', floor(true));
assertType('never', floor(false));
assertType('never', floor(new \stdClass));
assertType('never', floor(''));
assertType('never', floor(array()));
assertType('never', floor(array(123)));
