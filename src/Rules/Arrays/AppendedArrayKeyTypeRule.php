<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Assign>
 */
class AppendedArrayKeyTypeRule implements \PHPStan\Rules\Rule
{

	private PropertyReflectionFinder $propertyReflectionFinder;

	private bool $checkUnionTypes;

	public function __construct(
		PropertyReflectionFinder $propertyReflectionFinder,
		bool $checkUnionTypes
	)
	{
		$this->propertyReflectionFinder = $propertyReflectionFinder;
		$this->checkUnionTypes = $checkUnionTypes;
	}

	public function getNodeType(): string
	{
		return Assign::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		if (!($node->var instanceof ArrayDimFetch)) {
			return [];
		}

		if (
			!$node->var->var instanceof \PhpParser\Node\Expr\PropertyFetch
			&& !$node->var->var instanceof \PhpParser\Node\Expr\StaticPropertyFetch
		) {
			return [];
		}

		$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($node->var->var, $scope);
		if (count($propertyReflections) === 0) {
			return [];
		}

		$errors = [];
		foreach ($propertyReflections as $propertyReflection) {
			$arrayType = $propertyReflection->getReadableType();
			if (!$arrayType instanceof ArrayType) {
				continue;
			}

			if ($node->var->dim !== null) {
				$dimensionType = $scope->getType($node->var->dim);
				$isValidKey = AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimensionType);
				if (!$isValidKey->yes()) {
					// already handled by InvalidKeyInArrayDimFetchRule
					continue;
				}

				$keyType = ArrayType::castToArrayKeyType($dimensionType);
				if (!$this->checkUnionTypes && $keyType instanceof UnionType) {
					continue;
				}
			} else {
				$keyType = new IntegerType();
			}

			if ($arrayType->getIterableKeyType()->isSuperTypeOf($keyType)->yes()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(
				sprintf(
					'Array (%s) does not accept key %s.',
					$arrayType->describe(VerbosityLevel::typeOnly()),
					$keyType->describe(VerbosityLevel::value())
				)
			)->build();
		}

		return $errors;
	}

}
