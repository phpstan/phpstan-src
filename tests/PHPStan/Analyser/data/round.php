<?php

namespace RoundFamilyTest;

use function PHPStan\Testing\assertType;

// Round
assertType('float', round(123));
assertType('float', round(123.456));
assertType('float', round($_GET['foo'] / 60));
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
assertType('(float|false)', round($_GET['foo']));

// Ceil
assertType('float', ceil(123));
assertType('float', ceil(123.456));
assertType('float', ceil($_GET['foo'] / 60));
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
assertType('(float|false)', ceil($_GET['foo']));

// Floor
assertType('float', floor(123));
assertType('float', floor(123.456));
assertType('float', floor($_GET['foo'] / 60));
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
assertType('(float|false)', floor($_GET['foo']));
