<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use function strtolower;

class CtypeDigitFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'ctype_digit'
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!isset($node->getArgs()[0])) {
			return new SpecifiedTypes();
		}
		if ($context->null()) {
			throw new ShouldNotHappenException();
		}

		if ($context->true() && $scope->getType($node->getArgs()[0]->value)->isNumericString()->yes()) {
			return new SpecifiedTypes();
		}

		$types = [
			IntegerRangeType::fromInterval(48, 57), // ASCII-codes for 0-9
			IntegerRangeType::createAllGreaterThanOrEqualTo(256), // Starting from 256 ints are interpreted as strings
		];

		if ($context->true()) {
			$types[] = new IntersectionType([
				new StringType(),
				new AccessoryNumericStringType(),
			]);
		}

		return $this->typeSpecifier->create($node->getArgs()[0]->value, TypeCombinator::union(...$types), $context, false, $scope);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
