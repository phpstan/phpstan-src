<?php

declare(strict_types = 1);

namespace Bug14566;

use function PHPStan\Testing\assertType;

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function foo(array $test): void {
	if (isset($test['hi']) && is_string($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: array{0: 42, 1?: 42}}", $test);
	assertType("array{0: 42, 1?: 42}", $test['hi']);
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function fooOr(array $test): void {
	if (!isset($test['hi']) || !is_string($test['hi'])) {
		assertType("array{}|array{hi: array{0: 42, 1?: 42}}", $test);
		return;
	}
	assertType("array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: 42}|array{hi: 'hello'} $test
 */
function fooIsInt(array $test): void {
	if (isset($test['hi']) && is_int($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42}} $test
 */
function fooIsArray(array $test): void {
	if (isset($test['hi']) && is_array($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: \stdClass}|array{hi: 'hello'} $test
 */
function fooInstanceof(array $test): void {
	if (isset($test['hi']) && $test['hi'] instanceof \stdClass) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: string|int}|array{hi: float} $test
 */
function fooPartialOverlap(array $test): void {
	if (isset($test['hi']) && is_string($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: float}|array{hi: int}", $test);
}

/**
 * @param array{}|array{hi: string|int}|array{hi: float} $test
 */
function fooPartialOverlapOr(array $test): void {
	if (!isset($test['hi']) || !is_string($test['hi'])) {
		assertType("array{}|array{hi: float}|array{hi: int}", $test);
		return;
	}
	assertType("array{hi: string}", $test);
}

/**
 * Regression: conditional holders for property fetches must use the right-side
 * scope (where the base object is narrowed) to precompute the target type.
 * Otherwise, accessing $node->name when $node is CallLike (which has no $name
 * property) produces ErrorType.
 */
function fooElseifPropertyNarrowing(\PhpParser\Node\Expr\CallLike $node, \PHPStan\Analyser\Scope $scope): void {
	if ($node instanceof \PhpParser\Node\Expr\MethodCall && $node->name instanceof \PhpParser\Node\Identifier) {
		assertType('PhpParser\Node\Expr\MethodCall', $node);
		assertType('PhpParser\Node\Identifier', $node->name);
	} elseif ($node instanceof \PhpParser\Node\Expr\StaticCall && $node->name instanceof \PhpParser\Node\Identifier && $node->class instanceof \PhpParser\Node\Name) {
		assertType('PhpParser\Node\Expr\StaticCall', $node);
		assertType('PhpParser\Node\Identifier', $node->name);
		assertType('PhpParser\Node\Name', $node->class);
	} elseif ($node instanceof \PhpParser\Node\Expr\New_ && $node->class instanceof \PhpParser\Node\Name) {
		assertType('PhpParser\Node\Expr\New_', $node);
		assertType('PhpParser\Node\Name', $node->class);
	} elseif ($node instanceof \PhpParser\Node\Expr\FuncCall && $node->name instanceof \PhpParser\Node\Name) {
		assertType('PhpParser\Node\Expr\FuncCall', $node);
		assertType('PhpParser\Node\Name', $node->name);
	} elseif ($node instanceof \PhpParser\Node\Expr\FuncCall) {
		assertType('PhpParser\Node\Expr\FuncCall', $node);
		assertType('PhpParser\Node\Expr', $node->name);
	}
}

class FooContainer {
	/** @var \stdClass|string */
	public $x;
	/** @var \stdClass|int */
	public $y;
}

function fooPropertyFetchInstanceof(FooContainer $c): void {
	if ($c->x instanceof \stdClass && $c->y instanceof \stdClass) {
		return;
	}
	if ($c->x instanceof \stdClass) {
		assertType('int', $c->y);
	}
}

function fooPropertyFetchInstanceofOr(FooContainer $c): void {
	if (!$c->x instanceof \stdClass || !$c->y instanceof \stdClass) {
		if ($c->x instanceof \stdClass) {
			assertType('int', $c->y);
		}
		return;
	}
	assertType('stdClass', $c->x);
	assertType('stdClass', $c->y);
}
