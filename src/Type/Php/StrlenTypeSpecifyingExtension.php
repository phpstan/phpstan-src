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
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerRangeType;
use function count;
use function in_array;
use function strtolower;

final class StrlenTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return in_array(strtolower($functionReflection->getName()), ['strlen', 'mb_strlen'], true) && !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();
		if (
			count($args) < 1
			|| $context->getReturnType() === null
		) {
			return new SpecifiedTypes();
		}

		$argType = $scope->getType($args[0]->value);
		if (!$argType->isString()->yes()) {
			return new SpecifiedTypes();
		}

		$returnType = $context->getReturnType();
		if (
			$context->true() && IntegerRangeType::createAllGreaterThanOrEqualTo(1)->isSuperTypeOf($returnType)->yes()
			|| ($context->false() && (new ConstantIntegerType(0))->isSuperTypeOf($returnType)->yes())
		) {
			$accessory = new AccessoryNonEmptyStringType();
			if (IntegerRangeType::createAllGreaterThanOrEqualTo(2)->isSuperTypeOf($returnType)->yes()) {
				$accessory = new AccessoryNonFalsyStringType();
			}

			return $this->typeSpecifier->create($args[0]->value, $accessory, $context, $scope)->setRootExpr($node);
		}

		return new SpecifiedTypes();
	}

}
