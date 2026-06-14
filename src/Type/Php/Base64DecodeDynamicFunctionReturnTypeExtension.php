<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function base64_decode;
use function count;

#[AutowiredService]
final class Base64DecodeDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'base64_decode';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return new StringType();
		}

		if (!isset($args[1])) {
			return $this->resolveType($scope, $args[0]->value, false) ?? new StringType();
		}

		$argType = $scope->getType($args[1]->value);

		if ($argType instanceof MixedType) {
			return new BenevolentUnionType([new StringType(), new ConstantBooleanType(false)]);
		}

		$isTrueType = $argType->isTrue();
		$isFalseType = $argType->isFalse();
		$compareTypes = $isTrueType->compareTo($isFalseType);
		if ($compareTypes === $isTrueType) {
			return $this->resolveType($scope, $args[0]->value, true)
				?? new UnionType([new StringType(), new ConstantBooleanType(false)]);
		}
		if ($compareTypes === $isFalseType) {
			return $this->resolveType($scope, $args[0]->value, false) ?? new StringType();
		}

		// second argument could be interpreted as true
		if (!$isTrueType->no()) {
			return new UnionType([new StringType(), new ConstantBooleanType(false)]);
		}

		return $this->resolveType($scope, $args[0]->value, false) ?? new StringType();
	}

	private function resolveType(Scope $scope, Expr $stringArg, bool $strict): ?Type
	{
		$constantStrings = $scope->getType($stringArg)->getConstantStrings();
		if (count($constantStrings) === 0) {
			return null;
		}

		$resultTypes = [];
		foreach ($constantStrings as $constantString) {
			$decoded = base64_decode($constantString->getValue(), true);
			if ($decoded === false) {
				// In non-strict mode base64_decode is lenient about invalid input,
				// so leave the result as a generic string instead of guessing the value.
				if (!$strict) {
					return null;
				}

				$resultTypes[] = new ConstantBooleanType(false);
				continue;
			}

			$resultTypes[] = new ConstantStringType($decoded);
		}

		return TypeCombinator::union(...$resultTypes);
	}

}
