<?php

namespace IncompatibleDefaultParameter;

/**
 * @param int $int
 */
function takesInt($int = 10): void
{
}

/**
 * @param string $string
 */
function takesString($string = false): void
{
}

/**
 * @param array{opt?: int, req: int} $arr
 */
function takesArrayShape($arr = ['req' => 1, 'foo' => 2]): void
{
}
