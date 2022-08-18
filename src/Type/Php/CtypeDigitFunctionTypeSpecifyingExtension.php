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
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
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

		$argType = $scope->getType($node->getArgs()[0]->value);
		$intRange = new UnionType([
			IntegerRangeType::fromInterval(48, 57), // ASCII-codes for 0-9
			IntegerRangeType::createAllGreaterThanOrEqualTo(256) // Starting from 256 ints are interpreted as strings
		]);

		if ((new StringType())->isSuperTypeOf($argType)->yes()) {
			return $this->typeSpecifier->create($node->getArgs()[0]->value, new AccessoryNumericStringType(), $context, false, $scope);
		}

		if ((new IntegerType())->isSuperTypeOf($argType)->yes()) {
			return $this->typeSpecifier->create($node->getArgs()[0]->value, $intRange, $context, false, $scope);
		}

		return $this->typeSpecifier->create($node->getArgs()[0]->value, TypeCombinator::union(new AccessoryNumericStringType(), $intRange), $context, false, $scope);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
