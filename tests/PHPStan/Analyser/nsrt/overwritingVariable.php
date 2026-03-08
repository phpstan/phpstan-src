<?php

namespace OverwritingVariable;

use function PHPStan\Testing\assertType;

assertType('mixed', $var);

$var = new Bar();
assertType('OverwritingVariable\Bar', $var);
$var = $var->methodFoo();

assertType('OverwritingVariable\Foo', $var);
