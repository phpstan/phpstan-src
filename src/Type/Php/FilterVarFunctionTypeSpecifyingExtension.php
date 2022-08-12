<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use function count;
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
		if (in_array($flagsType->getValue(), [FILTER_VALIDATE_BOOL, FILTER_VALIDATE_BOOLEAN], true)) {
			$type = new BooleanType();
		}
		if (in_array($flagsType->getValue(), [FILTER_VALIDATE_DOMAIN, FILTER_VALIDATE_URL, FILTER_VALIDATE_EMAIL], true)) {
			$type = new StringType();
		}
		if ($flagsType->getValue() === FILTER_VALIDATE_INT) {
			$type = new IntegerType();
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
