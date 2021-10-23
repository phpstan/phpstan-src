<?php

namespace RoundFamilyTest;

use function PHPStan\Testing\assertType;

// Round
assertType('float', round(123));
assertType('float', round(123.456));
assertType('float', round('123'));
assertType('float', round('123.456'));
assertType('false', round(array()));

// Ceil
assertType('float', ceil(123));
assertType('float', ceil(123.456));
assertType('float', ceil('123'));
assertType('float', ceil('123.456'));
assertType('false', ceil(array()));

// Floor
assertType('float', floor(123));
assertType('float', floor(123.456));
assertType('float', floor('123'));
assertType('float', floor('123.456'));
assertType('false', floor(array()));
