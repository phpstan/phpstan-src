<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use function count;
use function in_array;
use function strtolower;

final class StrContainingTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	/** @var string[] */
	private array $strContainingFunctions = [
		'str_contains',
		'str_starts_with',
		'str_ends_with',
	];

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return in_array(strtolower($functionReflection->getName()), $this->strContainingFunctions, true)
			&& $context->true();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();

		if (count($args) >= 2) {
			$haystackType = $scope->getType($args[0]->value);
			$needleType = $scope->getType($args[1]->value);

			if ($needleType->isNonEmptyString()->yes() && $haystackType->isString()->yes()) {
				$accessories = [
					new StringType(),
					new AccessoryNonEmptyStringType(),
				];

				if ($haystackType->isLiteralString()->yes()) {
					$accessories[] = new AccessoryLiteralStringType();
				}
				if ($haystackType->isNumericString()->yes()) {
					$accessories[] = new AccessoryNumericStringType();
				}

				return $this->typeSpecifier->create(
					$args[0]->value,
					new IntersectionType($accessories),
					$context,
					false,
					$scope,
				);
			}
		}

		return new SpecifiedTypes();
	}

}
