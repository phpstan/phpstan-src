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
 * @param float $float
 */
function takesFloatWithIntDefault($float = 1) : void
{
}