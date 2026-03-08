<?php

namespace AnonymousClassNameSameLine;

use function PHPStan\Testing\assertType;

$foo = new class {};
assertType('AnonymousClassfcfd1128d5cdd5460674a8fce4cda7ec', $foo);
$bar = new class {};
assertType('AnonymousClassfac55c26b2d5e8b973121a9b7b6e182b', $bar);
$baz = new class {};
assertType('AnonymousClass6695c569a4327b62b4787d88886fdafd', $baz);
