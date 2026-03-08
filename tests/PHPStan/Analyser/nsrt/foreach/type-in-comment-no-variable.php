<?php

namespace TypeInCommentOnForeach;

use function PHPStan\Testing\assertType;

/** @var mixed[] $values */
$values = [];

/** @var bool */
foreach ($values as $value) {
	assertType('bool', $value);
}
