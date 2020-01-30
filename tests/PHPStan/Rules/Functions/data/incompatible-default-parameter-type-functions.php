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

/**
 * @template T
 * @param T $x
 */
function takesTemplateWithIntDefault($x = 1) : void
{
}