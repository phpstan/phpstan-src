<?php

namespace ClosureReturnTypeExtensionsNamespace;

use function PHPStan\Testing\assertType;

$strtotimeNow = strtotime('2022-03-03 12:00:00 UTC');
assertType('int<1646308801, max>', $strtotimeNow);

$strtotimeInvalid = strtotime('4 qm');
assertType('false', $strtotimeInvalid);

$strtotimeUnknown = strtotime(rand(0, 1) === 0 ? 'now': '4 qm');
assertType('int|false', $strtotimeUnknown);

$strtotimeUnknown2 = strtotime($undefined);
assertType('(int|false)', $strtotimeUnknown2);

$strtotimeCrash = strtotime();
assertType('int|false', $strtotimeCrash);

$strtotimeWithBase = strtotime('+2 days', time());
assertType('int|false', $strtotimeWithBase);

$strtotimePositiveInt = strtotime('1990-01-01 12:00:00 UTC');
assertType('int<631195201, max>', $strtotimePositiveInt);

$strtotimeNegativeInt = strtotime('1969-12-31 12:00:00 UTC');
assertType('int<-43199, max>', $strtotimeNegativeInt);
