<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

#[AutowiredService]
final class MicrotimeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'microtime';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$stringType = new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()]);

		if (count($functionCall->getArgs()) < 1) {
			return $stringType;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$isTrueType = $argType->isTrue();
		$isFalseType = $argType->isFalse();
		$compareTypes = $isTrueType->compareTo($isFalseType);
		if ($compareTypes === $isTrueType) {
			return new FloatType();
		}
		if ($compareTypes === $isFalseType) {
			return $stringType;
		}

		if ($argType instanceof MixedType) {
			return new BenevolentUnionType([$stringType, new FloatType()]);
		}

		return new UnionType([$stringType, new FloatType()]);
	}

}
