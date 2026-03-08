<?php

namespace TypeInCommentOnForeach;

use function PHPStan\Testing\assertType;

/** @var mixed[] $values */
$values = [];

/** @var int $wrongValue */
foreach ($values as $value) {
	assertType('mixed', $value);
}
