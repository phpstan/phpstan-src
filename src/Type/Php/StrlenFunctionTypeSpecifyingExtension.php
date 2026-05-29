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
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
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

		$narrowedReturnType = $context->getNarrowedReturnType();
		if ($narrowedReturnType !== null) {
			return $this->specifyTypesForLengthCondition($node, $narrowedReturnType, $context, $scope);
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

	/**
	 * Narrows the string argument when the strlen() result is constrained to a known length range,
	 * e.g. `strlen($s) >= 1` makes $s non-empty-string and `>= 2` makes it non-falsy-string.
	 */
	private function specifyTypesForLengthCondition(
		FuncCall $node,
		Type $narrowedReturnType,
		TypeSpecifierContext $context,
		Scope $scope,
	): SpecifiedTypes
	{
		$oneOrMore = IntegerRangeType::createAllGreaterThanOrEqualTo(1);

		if ($context->true() && $oneOrMore->isSuperTypeOf($narrowedReturnType)->yes()) {
			$accessory = new AccessoryNonEmptyStringType();
			if (IntegerRangeType::createAllGreaterThanOrEqualTo(2)->isSuperTypeOf($narrowedReturnType)->yes()) {
				$accessory = new AccessoryNonFalsyStringType();
			}

			return $this->typeSpecifier->create($node->getArgs()[0]->value, $accessory, $context, $scope)->setRootExpr($node);
		}

		// The condition fails only when the length is below the range. We can conclude the string is
		// empty (i.e. not a non-empty-string) only when the range starts exactly at 1.
		if ($context->false() && $oneOrMore->equals($narrowedReturnType)) {
			return $this->typeSpecifier->create($node->getArgs()[0]->value, new AccessoryNonEmptyStringType(), $context, $scope)->setRootExpr($node);
		}

		return new SpecifiedTypes([], []);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
