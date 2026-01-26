<?php declare(strict_types=1);

namespace Bug14012;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use function PHPStan\Testing\assertType;

final class ExpectationMethodResolver
{
	/**
	 * @var array{
	 *   hasMethod: \Closure(Scope, Node\Arg, Node\Arg): Node\Expr,
	 *   hasProperty: \Closure(Scope, Node\Arg, Node\Arg): Node\Expr,
	 *   isArray: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isBool: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isCallable: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isCountable: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isFalse: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isFloat: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isInstanceOf: \Closure(Scope, Node\Arg, Node\Arg): Node\Expr,
	 *   isInt: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isIterable: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isList: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isMap: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isNaturalInt: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isNegativeInt: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isNonEmptyString: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isNull: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isNumeric: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isObject: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isPositiveInt: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isResource: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isSameAs: \Closure(Scope, Node\Arg, Node\Arg): Node\Expr,
	 *   isScalar: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isString: \Closure(Scope, Node\Arg): Node\Expr,
	 *   isTrue: \Closure(Scope, Node\Arg): Node\Expr,
	 * }
	 */
	public static array $resolvers = [];
}

assertType("non-empty-array<'hasMethod'|'hasProperty'|'isArray'|'isBool'|'isCallable'|'isCountable'|'isFalse'|'isFloat'|'isInstanceOf'|'isInt'|'isIterable'|'isList'|'isMap'|'isNaturalInt'|'isNegativeInt'|'isNonEmptyString'|'isNull'|'isNumeric'|'isObject'|'isPositiveInt'|'isResource'|'isSameAs'|'isScalar'|'isString'|'isTrue', callable(): mixed>&oversized-array", ExpectationMethodResolver::$resolvers);
