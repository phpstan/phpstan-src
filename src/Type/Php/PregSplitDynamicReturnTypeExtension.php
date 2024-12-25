<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function strtolower;

final class PregSplitDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private BitwiseFlagHelper $bitwiseFlagAnalyser,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'preg_split';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}
		$patternArg = $args[0];
		$subjectArg = $args[1];
		$limitArg = $args[2] ?? null;
		$flagArg = $args[3] ?? null;
		$patternType = $scope->getType($patternArg->value);
		$patternConstantTypes = $patternType->getConstantStrings();
		$subjectType = $scope->getType($subjectArg->value);
		$subjectConstantTypes = $subjectType->getConstantStrings();

		if (
			count($patternConstantTypes) > 0 &&
			@preg_match($patternConstantTypes[0]->getValue(), "") === false
		) {

			return new ErrorType();
		}

		if ($subjectArg !== null && $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($subjectArg->value, $scope, 'PREG_SPLIT_OFFSET_CAPTURE')->yes()) {
			$type = new ArrayType(
				new IntegerType(),
				new ConstantArrayType([new ConstantIntegerType(0), new ConstantIntegerType(1)], [new StringType(), IntegerRangeType::fromInterval(0, null)], [2], [], TrinaryLogic::createYes()),
			);
			return TypeCombinator::union(TypeCombinator::intersect($type, new AccessoryArrayListType()), new ConstantBooleanType(false));
		}

		if ($limitArg === null) {
			$limits = [-1];
		} else {
			$limitType = $scope->getType($limitArg->value);
			$limits = $limitType->getConstantScalarValues();
		}

		if ($flagArg === null) {
			$flags = [0];
		} else {
			$flagType = $scope->getType($flagArg->value);
			$flags = $flagType->getConstantScalarValues();
		}

		if (count($patternConstantTypes) === 0 || count($subjectConstantTypes) === 0 || count($flags) === 0) {
			return null;
		}

		$resultTypes = [];
		foreach ($patternConstantTypes as $patternConstantType) {
			foreach ($subjectConstantTypes as $subjectConstantType) {
				foreach ($flags as $flag) {
					foreach ($limits as $limit) {
						$result = @preg_split($patternConstantType->getValue(), $subjectConstantType->getValue(), $limit, $flag);
						if ($result !== false) {
							$constantArray = ConstantArrayTypeBuilder::createEmpty();
							foreach ($result as $key => $value) {
								assert(is_int($key));
								if (is_array($value)) {
									$valueConstantArray = ConstantArrayTypeBuilder::createEmpty();
									$valueConstantArray->setOffsetValueType(new ConstantIntegerType(0), new ConstantStringType($value[0]));
									$valueConstantArray->setOffsetValueType(new ConstantIntegerType(1), new ConstantIntegerType($value[1]));
									$valueType = $valueConstantArray->getArray();
								} else {
									$valueType = new ConstantStringType($value);
								}
								$constantArray->setOffsetValueType(new ConstantIntegerType($key), $valueType);
							}
							$resultTypes[] = $constantArray->getArray();
						}
					}
				}
			}
		}
		return TypeCombinator::union(...$resultTypes);
	}
}
