<?php

declare(strict_types=1);

namespace Bug13801;

/**
 * @template TValue of object
 */
interface Cast
{
}

/**
 * @template TCast of Cast<static>
 */
interface Castable
{
}
