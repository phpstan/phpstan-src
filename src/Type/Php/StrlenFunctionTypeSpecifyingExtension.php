<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\StaticTypeFactory;
use function count;
use function in_array;

#[AutowiredService]
final class StrlenFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return !$context->null()
			&& count($node->getArgs()) >= 1
			&& in_array($functionReflection->getName(), ['strlen', 'mb_strlen'], true);
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		if (!$scope->getType($node->getArgs()[0]->value)->isString()->yes()) {
			return new SpecifiedTypes([], []);
		}

		$argSpecifiedTypes = $this->typeSpecifier->create($node->getArgs()[0]->value, new AccessoryNonEmptyStringType(), $context, $scope);

		if ($context->truthy()) {
			return $this->typeSpecifier->create($node, StaticTypeFactory::falsey(), TypeSpecifierContext::createFalse(), $scope)
				->setRootExpr($node)
				->unionWith($argSpecifiedTypes);
		}

		return $this->typeSpecifier->create($node, StaticTypeFactory::truthy(), TypeSpecifierContext::createFalse(), $scope)
			->setRootExpr($node)
			->unionWith($argSpecifiedTypes);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
