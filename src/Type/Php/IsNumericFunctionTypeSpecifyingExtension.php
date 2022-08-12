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
use PHPStan\Type\FloatType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use function in_array;

class IsNumericFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return in_array($functionReflection->getName(), ['is_numeric', 'ctype_digit'], true)
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

		$numericTypes = [
			new IntegerType(),
			new FloatType(),
		];

		if ($context->truthy()) {
			$numericTypes[] = new IntersectionType([
				new StringType(),
				new AccessoryNumericStringType(),
			]);
		}

		return $this->typeSpecifier->create($node->getArgs()[0]->value, new UnionType($numericTypes), $context, false, $scope);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
