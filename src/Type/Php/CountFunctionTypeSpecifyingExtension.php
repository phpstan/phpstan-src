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
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use function count;
use function in_array;

#[AutowiredService]
final class CountFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(private CountFuncCallTypeSpecifier $countFuncCallTypeSpecifier)
	{
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return !$context->null()
			&& count($node->getArgs()) >= 1
			&& in_array($functionReflection->getName(), ['sizeof', 'count'], true);
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$argType = $scope->getType($node->getArgs()[0]->value);

		$narrowedReturnType = $context->getNarrowedReturnType();
		if ($narrowedReturnType !== null) {
			$specifiedTypes = $this->countFuncCallTypeSpecifier->specifyTypesForCountFuncCall(
				$this->typeSpecifier,
				$node,
				$argType,
				$narrowedReturnType,
				$context,
				$scope,
				$node,
			);
			if ($specifiedTypes !== null) {
				return $specifiedTypes;
			}

			return new SpecifiedTypes([], []);
		}

		if (!$argType->isArray()->yes()) {
			return new SpecifiedTypes([], []);
		}

		return $this->typeSpecifier->create($node->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
