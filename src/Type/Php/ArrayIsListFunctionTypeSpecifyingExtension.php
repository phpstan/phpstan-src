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
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;
use function strtolower;

/**
 * An array that array_is_list() rejects holds something: the empty array is a
 * list. So `!array_is_list($a)` makes an array `$a` non-empty, also where its
 * type says it is never a list, as `array<string, int>` - PHPStan treats an
 * array whose keys are no list keys as never a list, which the empty one is.
 */
#[AutowiredService]
final class ArrayIsListFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'array_is_list'
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();
		if (!isset($args[0])) {
			return new SpecifiedTypes();
		}
		if ($context->null()) {
			throw new ShouldNotHappenException();
		}

		$listType = TypeCombinator::intersect(
			new ArrayType(IntegerRangeType::fromInterval(0, null), new MixedType()),
			new AccessoryArrayListType(),
		);
		$specifiedTypes = $this->typeSpecifier->create($args[0]->value, $listType, $context, $scope);
		$valueType = $scope->getType($args[0]->value);
		if ($context->true() || !$valueType->isArray()->yes()) {
			return $specifiedTypes;
		}

		return $specifiedTypes->unionWith(
			$this->typeSpecifier->create($args[0]->value, new NonEmptyArrayType(), TypeSpecifierContext::createTrue(), $scope),
		);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
