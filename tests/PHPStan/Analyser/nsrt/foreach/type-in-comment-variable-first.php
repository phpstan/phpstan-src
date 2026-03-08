<?php

namespace TypeInCommentOnForeach;

use function PHPStan\Testing\assertType;

/** @var mixed[] $values */
$values = [];

/** @var $value callable */
foreach ($values as $value) {
	assertType('mixed', $value);
}
