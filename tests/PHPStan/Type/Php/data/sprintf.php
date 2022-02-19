<?php

namespace PHPStan\Type\Php\Test;

use function PHPStan\Testing\assertType;

$foo = 'foo';
assertType("'foo'", sprintf('%s', $foo));

function foo (string $nonLiteral): void {
	assertType('non-empty-string', sprintf('%s', $nonLiteral));
}

/** @var literal-string $literal */
$literal = '';
assertType('literal-string&non-empty-string', sprintf('%s', $literal));

function bar (int $int): void {
	assertType('literal-string&non-empty-string', sprintf('%d', $int));
}

/** @var int|literal-string|float $union */
$union = '';
assertType('literal-string&non-empty-string', sprintf('%s', $union));

/** @var int|string|float $union */
$union = '';
assertType('non-empty-string', sprintf('%s', $union));

