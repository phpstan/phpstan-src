<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Regexp\RegularExpressionHelper;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function strtolower;

class PregSplitDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private BitwiseFlagHelper $bitwiseFlagAnalyser,
		private RegularExpressionHelper $regularExpressionHelper,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'preg_split';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$flagsArg = $functionCall->getArgs()[3] ?? null;

		if ($flagsArg !== null && $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($flagsArg->value, $scope, 'PREG_SPLIT_OFFSET_CAPTURE')->yes()) {
			$type = new ArrayType(
				new IntegerType(),
				new ConstantArrayType([new ConstantIntegerType(0), new ConstantIntegerType(1)], [new StringType(), IntegerRangeType::fromInterval(0, null)], [2], [], TrinaryLogic::createYes()),
			);
			$returnType = TypeCombinator::union(AccessoryArrayListType::intersectWith($type), new ConstantBooleanType(false));
		} else {
			$returnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$patternArg = $functionCall->getArgs()[0] ?? null;
		if ($patternArg === null) {
			return $returnType;
		}

		$patternType = $scope->getType($patternArg->value);
		$constantStrings = $patternType->getConstantStrings();
		if (count($constantStrings) === 0) {
			return $returnType;
		}

		foreach ($constantStrings as $constantString) {
			if ($this->regularExpressionHelper->validatePattern($constantString->getValue()) !== null) {
				return $returnType;
			}
		}

		return TypeCombinator::remove(
			$returnType,
			new ConstantBooleanType(false),
		);
	}

}
