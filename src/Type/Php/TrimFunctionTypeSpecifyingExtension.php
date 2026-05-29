<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use function count;
use function in_array;
use function strtolower;

/**
 * Narrows the string argument of trim() and friends when its result is known not to be the empty
 * string, e.g. `trim($s) !== ''` makes $s a non-empty-string. Driven by the narrowed return type carried by
 * the comparison (TypeSpecifierContext::getNarrowedReturnType()).
 */
#[AutowiredService]
final class TrimFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return $context->getNarrowedReturnType() !== null
			&& $context->false()
			&& $node->name instanceof Name
			&& !$node->isFirstClassCallable()
			&& isset($node->getArgs()[0])
			&& in_array(strtolower($functionReflection->getName()), [
				'trim', 'ltrim', 'rtrim', 'chop',
				'mb_trim', 'mb_ltrim', 'mb_rtrim',
			], true);
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$narrowedReturnType = $context->getNarrowedReturnType();
		if ($narrowedReturnType === null) {
			return new SpecifiedTypes();
		}

		$constantStrings = $narrowedReturnType->getConstantStrings();
		if (count($constantStrings) !== 1 || $constantStrings[0]->getValue() !== '') {
			return new SpecifiedTypes();
		}

		$argValue = $node->getArgs()[0]->value;
		if (!$scope->getType($argValue)->isString()->yes()) {
			return new SpecifiedTypes();
		}

		return $this->typeSpecifier->create(
			$argValue,
			new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]),
			$context->negate(),
			$scope,
		);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
