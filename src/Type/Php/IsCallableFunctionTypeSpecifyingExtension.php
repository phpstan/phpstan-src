<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use function count;
use function strtolower;

#[AutowiredService]
final class IsCallableFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(private MethodExistsTypeSpecifyingExtension $methodExistsExtension)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'is_callable'
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			throw new ShouldNotHappenException();
		}

		if (!isset($node->getArgs()[0])) {
			return new SpecifiedTypes();
		}

		$value = $node->getArgs()[0]->value;
		$valueType = $scope->getType($value);
		if (
			$value instanceof Array_
			&& count($value->items) === 2
			&& $valueType->isConstantArray()->yes()
			&& !$valueType->isCallable()->no()
		) {
			$functionCall = new FuncCall(new Name('method_exists'), [
				new Arg($value->items[0]->value),
				new Arg($value->items[1]->value),
			]);
			$methodExistsTypes = $this->methodExistsExtension->specifyTypes($functionReflection, $functionCall, $scope, $context);

			$isCallableMarker = $this->typeSpecifier->create(
				new FuncCall(new FullyQualified('is_callable'), [
					new Arg($value->items[0]->value),
					new Arg($value->items[1]->value),
				]),
				new ConstantBooleanType(true),
				$context,
				$scope,
			);

			return $methodExistsTypes->unionWith($isCallableMarker);
		}

		return $this->typeSpecifier->create($value, new CallableType(), $context, $scope);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
