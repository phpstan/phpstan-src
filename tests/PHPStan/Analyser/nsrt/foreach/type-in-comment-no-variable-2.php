<?php

namespace TypeInCommentOnForeach;

use function PHPStan\Testing\assertType;

/** @var mixed[] $values */
$values = [];

/** @var bool */
foreach ($values as $key => $value) {
	assertType('*ERROR*', $value);
}
