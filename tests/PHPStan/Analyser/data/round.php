<?php

namespace RoundFamilyTest;

use function PHPStan\Testing\assertType;

// Round
assertType('float', round(123));
assertType('float', round(123.456));
assertType('float', round('123'));
assertType('float', round('123.456'));
assertType('float', round(null));
assertType('float', round(true));
assertType('float', round(false));
assertType('float', round(new \stdClass));
assertType('float', round(''));
assertType('false', round(array()));
assertType('false', round(array(123)));
assertType('null', round());

// Ceil
assertType('float', ceil(123));
assertType('float', ceil(123.456));
assertType('float', ceil('123'));
assertType('float', ceil('123.456'));
assertType('float', ceil(null));
assertType('float', ceil(true));
assertType('float', ceil(false));
assertType('float', ceil(new \stdClass));
assertType('float', ceil(''));
assertType('false', ceil(array()));
assertType('false', ceil(array(123)));
assertType('null', ceil());

// Floor
assertType('float', floor(123));
assertType('float', floor(123.456));
assertType('float', floor('123'));
assertType('float', floor('123.456'));
assertType('float', floor(null));
assertType('float', floor(true));
assertType('float', floor(false));
assertType('float', floor(new \stdClass));
assertType('float', floor(''));
assertType('false', floor(array()));
assertType('false', floor(array(123)));
assertType('null', floor());
