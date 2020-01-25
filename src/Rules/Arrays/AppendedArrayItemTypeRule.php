<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GenericTypeVariableResolver;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class AppendedArrayItemTypeRule implements \PHPStan\Rules\Rule
{

	/** @var \PHPStan\Rules\Properties\PropertyReflectionFinder */
	private $propertyReflectionFinder;

	/** @var \PHPStan\Rules\RuleLevelHelper */
	private $ruleLevelHelper;

	public function __construct(
		PropertyReflectionFinder $propertyReflectionFinder,
		RuleLevelHelper $ruleLevelHelper
	)
	{
		$this->propertyReflectionFinder = $propertyReflectionFinder;
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Assign
			&& !$node instanceof AssignOp
		) {
			return [];
		}

		if (!($node->var instanceof ArrayDimFetch)) {
			return [];
		}

		if ($node instanceof Assign) {
			$assignedValueType = $scope->getType($node->expr);
		} else {
			$assignedValueType = $scope->getType($node);
		}

		if ($node->var->var instanceof Variable) {
			$varTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->var->var,
				'',
				static function (Type $varType): bool {
					return $varType instanceof ObjectType && $varType->isInstanceOf(\ArrayAccess::class)->yes();
				}
			);
			$varType = $varTypeResult->getType();
			if ($varType instanceof ErrorType) {
				return [];
			}

			if (!$varType instanceof ObjectType || !$varType->isInstanceOf(\ArrayAccess::class)->yes()) {
				return [];
			}

			$tValue = GenericTypeVariableResolver::getType($varType, \ArrayAccess::class, 'TValue');
			if ($tValue === null) {
				return [];
			}

			if (!$this->ruleLevelHelper->accepts($tValue, $assignedValueType, $scope->isDeclareStrictTypes())) {
				$verbosityLevel = VerbosityLevel::typeOnly();
				if ($varType->getClassName() == \ArrayAccess::class) {
					$varName = $varType->describe($verbosityLevel);
				} else {
					$tKey = GenericTypeVariableResolver::getType($varType, \ArrayAccess::class, 'TKey');
					if ($tKey === null) {
						$tKey = new MixedType();
					}
					$varName = sprintf(
						'%s implements ArrayAccess<%s,%s>',
						$varType->describe($verbosityLevel),
						$tKey->describe($verbosityLevel),
						$tValue->describe($verbosityLevel)
					);
				}

				return [
					RuleErrorBuilder::message(sprintf(
						'%s does not accept %s.',
						$varName,
						$assignedValueType->describe($verbosityLevel)
					))->build(),
				];
			}

			return [];
		}

		if (
			!$node->var->var instanceof \PhpParser\Node\Expr\PropertyFetch
			&& !$node->var->var instanceof \PhpParser\Node\Expr\StaticPropertyFetch
		) {
			return [];
		}

		$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($node->var->var, $scope);
		if ($propertyReflection === null) {
			return [];
		}

		$assignedToType = $propertyReflection->getWritableType();
		if (!($assignedToType instanceof ArrayType)) {
			return [];
		}

		$itemType = $assignedToType->getItemType();
		if (!$this->ruleLevelHelper->accepts($itemType, $assignedValueType, $scope->isDeclareStrictTypes())) {
			$verbosityLevel = $itemType->isCallable()->and($assignedValueType->isCallable())->yes() ? VerbosityLevel::value() : VerbosityLevel::typeOnly();
			return [
				RuleErrorBuilder::message(sprintf(
					'Array (%s) does not accept %s.',
					$assignedToType->describe($verbosityLevel),
					$assignedValueType->describe($verbosityLevel)
				))->build(),
			];
		}

		return [];
	}

}
