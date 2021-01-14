<?php

namespace Bug3515;

/**
 * @var mixed[] $anArray
 */
$value1 = $anArray[0];
$value2 = $anArray[1];

/** @var int $foo */
$bar = $foo + 1;

function (): void
{
	/**
	 * @var mixed[] $anArray
	 */
	$value1 = $anArray[0];
	$value2 = $anArray[1];
};
