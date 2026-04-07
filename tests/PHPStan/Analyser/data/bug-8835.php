<?php

declare(strict_types=1);

namespace Bug8835;

/** @template T */
interface I
{
}

/** @implements I<self::X> */
class B implements I
{
	const X = 'x';
}
