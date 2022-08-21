<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function defined;
use function in_array;
use function strtolower;
use const FILTER_VALIDATE_BOOL;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_DOMAIN;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;
use const FILTER_VALIDATE_URL;

class FilterVarFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return strtolower($functionReflection->getName()) === 'filter_var' && $context->true();
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		if (count($node->getArgs()) < 2) {
			return new SpecifiedTypes();
		}

		$flagsType = $scope->getType($node->getArgs()[1]->value);
		if (!$flagsType instanceof ConstantIntegerType) {
			return new SpecifiedTypes();
		}

		$type = null;
		if ($flagsType->getValue() === FILTER_VALIDATE_FLOAT) {
			$type = new FloatType();
		}

		// note that FILTER_VALIDATE_BOOLEAN tries to be smart, recognizing words like Yes, No, Off, On, both string
		// and native types of true and false, and is not case-sensitive when validating strings.
		if (defined('FILTER_VALIDATE_BOOL')) { // requires php 8.0+
			if (in_array($flagsType->getValue(), [FILTER_VALIDATE_BOOL, FILTER_VALIDATE_BOOLEAN], true)) {
				$type = new UnionType([
					new BooleanType(),
					new StringType(),
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				]);
			}
		} else {
			if ($flagsType->getValue() === FILTER_VALIDATE_BOOLEAN) {
				$type = new UnionType([
					new BooleanType(),
					new StringType(),
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				]);
			}
		}

		if (in_array($flagsType->getValue(), [FILTER_VALIDATE_DOMAIN, FILTER_VALIDATE_URL, FILTER_VALIDATE_EMAIL], true)) {
			$type = new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]);
		}
		// 0 will be filtered out and therefore wont be considered as an int by filter_var
		if ($flagsType->getValue() === FILTER_VALIDATE_INT) {
			$type = TypeCombinator::union(
				IntegerRangeType::fromInterval(null, -1),
				IntegerRangeType::fromInterval(1, null),
			);
		}

		if ($type !== null) {
			return $this->typeSpecifier->create(
				$node->getArgs()[0]->value,
				$type,
				$context,
				false,
				$scope,
			);
		}

		return new SpecifiedTypes();
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
