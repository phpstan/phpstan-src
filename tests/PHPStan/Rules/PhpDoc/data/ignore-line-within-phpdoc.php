<?php

use InvalidPhpDoc\Foo;

/**
 * @param Foo $valid
 *
 *
 *
 *  // @phpstan-ignore-next-line
 * @param ( $invalid2
 *
 * @param ( $invalid1 // @phpstan-ignore-line
 *
 * @param ( $invalid2 // @phpstan-ignore-line
 *
 *
 * // @phpstan-ignore-next-line
 * @param ( $invalid2
 *
 * @return void
 *
 * @uses Foo::bar()
 *
 */
function testIgnoreInsidePhpDoc()
{
}
