<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;

class SubstrDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
	private PhpVersion $phpVersion;

	public function __construct(PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'substr';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): \PHPStan\Type\Type
	{
		$args = $functionCall->args;
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$string = $scope->getType($args[0]->value);

		if (count($args) >= 2) {
			$offset = $scope->getType($args[1]->value);

			$negativeOffset = IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($offset)->yes();
			$zeroOffset = (new ConstantIntegerType(0))->isSuperTypeOf($offset)->yes();
			$positiveLength = false;

			if (count($args) === 3) {
				$length = $scope->getType($args[2]->value);
				$positiveLength = IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($length)->yes();
			}

			if ($string->isNonEmptyString()->yes() && ($negativeOffset || $zeroOffset && $positiveLength)) {
				  return new IntersectionType([
					  new StringType(),
					  new AccessoryNonEmptyStringType(),
				  ]);
			}
		}

		if ($string instanceof StringType) {
			if ($this->phpVersion->getVersionId() >= 80000) {
				return new StringType();
			}

			return TypeCombinator::union(
				new StringType(),
				new ConstantBooleanType(false)
			);
		}

		// since php8 substr() returns an empty string where it previously returned false.
		if ($this->phpVersion->getVersionId() >= 80000) {
			return new StringType();
		}
		return new ConstantBooleanType(false);
	}

}
