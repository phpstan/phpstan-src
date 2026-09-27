<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use function count;
use function in_array;
use function is_string;
use function mb_check_encoding;
use function mb_strlen;
use function strlen;
use function strtolower;

/**
 * Knows the maximum length of strings produced by expressions like
 * `substr($s, 0, 4)` or `chr($i)` whose type is just `string`.
 */
#[AutowiredService]
final class StringLengthBoundHelper
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	/**
	 * Whether every value of $otherType is a string longer than any string $expr can produce.
	 */
	public function exceedsMaxLength(Scope $scope, Expr $expr, Type $otherType, ?NodeScopeResolver $nodeScopeResolver = null): bool
	{
		if ($expr instanceof AlwaysRememberedExpr) {
			$expr = $expr->getExpr();
		}

		if (!$expr instanceof FuncCall) {
			return false;
		}

		$values = $otherType->getConstantScalarValues();
		if (count($values) === 0) {
			return false;
		}
		foreach ($values as $value) {
			if (!is_string($value)) {
				return false;
			}
		}

		$maxLength = $this->getMaxLength($scope, $expr, $nodeScopeResolver);
		if ($maxLength === null) {
			return false;
		}

		[$boundType, $fromNegativeOffset, $inCharacters] = $maxLength;
		foreach ($values as $value) {
			if ($inCharacters) {
				if (!mb_check_encoding($value)) {
					return false;
				}
				$length = mb_strlen($value);
			} else {
				$length = strlen($value);
			}

			$shorterBoundType = $fromNegativeOffset
				? IntegerRangeType::fromInterval(1 - $length, null)
				: IntegerRangeType::fromInterval(null, $length - 1);
			if (!$shorterBoundType->isSuperTypeOf($boundType)->yes()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return array{Type, bool, bool}|null the integer type bounding the length (a negative `$offset` when the first bool is true, the maximum length otherwise) and whether the length is measured in characters of the internal encoding instead of bytes
	 */
	private function getMaxLength(Scope $scope, FuncCall $expr, ?NodeScopeResolver $nodeScopeResolver): ?array
	{
		if (!$expr->name instanceof Name) {
			return null;
		}

		$args = [];
		foreach ($expr->getRawArgs() as $arg) {
			if (!$arg instanceof Arg || $arg->unpack || $arg->name !== null) {
				return null;
			}
			$args[] = $arg->value;
		}

		if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
			return null;
		}
		$functionName = strtolower($this->reflectionProvider->getFunction($expr->name, $scope)->getName());
		if (!in_array($functionName, ['substr', 'mb_substr', 'mb_strcut', 'chr'], true)) {
			return null;
		}

		if ($functionName === 'chr') {
			return count($args) === 1 ? [new ConstantIntegerType(1), false, false] : null;
		}

		if (count($args) < 2) {
			return null;
		}

		if (isset($args[3])) {
			return null;
		}

		$inCharacters = $functionName === 'mb_substr';

		if (isset($args[2])) {
			$lengthType = $this->getType($scope, $args[2], $nodeScopeResolver);
			if (!$lengthType->isNull()->yes()) {
				if (!IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($lengthType)->yes()) {
					return null;
				}

				return [$lengthType, false, $inCharacters];
			}
		}

		if ($functionName === 'mb_strcut') {
			return null;
		}

		$offsetType = $this->getType($scope, $args[1], $nodeScopeResolver);
		if (!IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($offsetType)->yes()) {
			return null;
		}

		return [$offsetType, true, $inCharacters];
	}

	private function getType(Scope $scope, Expr $expr, ?NodeScopeResolver $nodeScopeResolver): Type
	{
		return $nodeScopeResolver !== null
			? $nodeScopeResolver->readTypeOfMaybeStored($expr, $scope->toWalkScope())
			: $scope->getType($expr);
	}

}
