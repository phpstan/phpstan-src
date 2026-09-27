<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use function count;
use function in_array;
use function is_int;
use function is_string;
use function max;
use function mb_check_encoding;
use function mb_list_encodings;
use function mb_strlen;
use function min;
use function strlen;
use function strtolower;

/**
 * Knows the maximum length of strings produced by expressions like
 * `substr($s, 0, 4)` or `$s[0]` whose type is just `string`.
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

		if (!$expr instanceof FuncCall && !$expr instanceof ArrayDimFetch) {
			return false;
		}

		if (!$otherType->isConstantScalarValue()->yes()) {
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

		foreach ($values as $value) {
			$length = $maxLength[1] === null ? strlen($value) : $this->getCharacterCount($value, $maxLength[1]);
			if ($length === null || $length <= $maxLength[0]) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return array{int, string|true|null}|null the maximum length and how it is measured: null for bytes, true for characters in the internal encoding, a string for characters in that encoding
	 */
	private function getMaxLength(Scope $scope, Expr $expr, ?NodeScopeResolver $nodeScopeResolver): ?array
	{
		if ($expr instanceof ArrayDimFetch) {
			if ($expr->dim === null) {
				return null;
			}
			if (!$this->getType($scope, $expr->var, $nodeScopeResolver)->isString()->yes()) {
				return null;
			}

			return [1, null];
		}

		if (!$expr instanceof FuncCall || !$expr->name instanceof Name) {
			return null;
		}

		if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
			return null;
		}
		$functionName = strtolower($this->reflectionProvider->getFunction($expr->name, $scope)->getName());
		if (!in_array($functionName, ['substr', 'mb_substr', 'mb_strcut', 'chr'], true)) {
			return null;
		}

		$args = [];
		foreach ($expr->getRawArgs() as $arg) {
			if (!$arg instanceof Arg || $arg->unpack || $arg->name !== null) {
				return null;
			}
			$args[] = $arg->value;
		}

		if ($functionName === 'chr') {
			return count($args) === 1 ? [1, null] : null;
		}

		if (count($args) < 2) {
			return null;
		}

		$unit = null;
		if ($functionName === 'mb_substr') {
			$unit = true;
			if (isset($args[3])) {
				$encodings = $this->getType($scope, $args[3], $nodeScopeResolver)->getConstantStrings();
				if (count($encodings) !== 1 || !$this->isSupportedEncoding($encodings[0]->getValue())) {
					return null;
				}
				$unit = $encodings[0]->getValue();
			}
		}

		if (isset($args[2])) {
			$lengthType = $this->getType($scope, $args[2], $nodeScopeResolver);
			if (!$lengthType->isNull()->yes()) {
				$lengthValues = $this->getIntegerValues($lengthType);
				if ($lengthValues === null) {
					return null;
				}

				$maxLength = max($lengthValues);
				if (min($lengthValues) < 0) {
					return null;
				}

				return [$maxLength, $unit];
			}
		}

		if ($functionName === 'mb_strcut') {
			return null;
		}

		$offsetValues = $this->getIntegerValues($this->getType($scope, $args[1], $nodeScopeResolver));
		if ($offsetValues === null || max($offsetValues) >= 0) {
			return null;
		}

		return [-min($offsetValues), $unit];
	}

	/**
	 * @return non-empty-list<int>|null
	 */
	private function getIntegerValues(Type $type): ?array
	{
		if (!$type->isConstantScalarValue()->yes()) {
			return null;
		}

		$values = [];
		foreach ($type->getConstantScalarValues() as $value) {
			if (!is_int($value)) {
				return null;
			}
			$values[] = $value;
		}

		if (count($values) === 0) {
			return null;
		}

		return $values;
	}

	private function getCharacterCount(string $value, string|true $encoding): ?int
	{
		if ($encoding === true) {
			if (!mb_check_encoding($value)) {
				return null;
			}

			return mb_strlen($value);
		}

		if (!mb_check_encoding($value, $encoding)) {
			return null;
		}

		return mb_strlen($value, $encoding);
	}

	private function isSupportedEncoding(string $encoding): bool
	{
		$encoding = strtolower($encoding);
		foreach (mb_list_encodings() as $supportedEncoding) {
			if (strtolower($supportedEncoding) === $encoding) {
				return true;
			}
		}

		return false;
	}

	private function getType(Scope $scope, Expr $expr, ?NodeScopeResolver $nodeScopeResolver): Type
	{
		return $nodeScopeResolver !== null
			? $nodeScopeResolver->readTypeOfMaybeStored($expr, $scope->toWalkScope())
			: $scope->getType($expr);
	}

}
