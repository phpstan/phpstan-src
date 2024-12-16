<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function is_string;
use function sprintf;

/**
 * @implements Rule<PropertyAssignNode>
 */
final class TypesAssignedToPropertiesRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private PropertyReflectionFinder $propertyReflectionFinder,
	)
	{
	}

	public function getNodeType(): string
	{
		return PropertyAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$propertyFetch = $node->getPropertyFetch();
		$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($propertyFetch, $scope);

		$errors = [];
		foreach ($propertyReflections as $propertyReflection) {
			$errors = array_merge($errors, $this->processSingleProperty(
				$propertyReflection,
				$propertyFetch,
				$node->getAssignedExpr(),
			));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleProperty(
		FoundPropertyReflection $propertyReflection,
		PropertyFetch|StaticPropertyFetch $fetch,
		Node\Expr $assignedExpr,
	): array
	{
		if (!$propertyReflection->isWritable()) {
			return [];
		}

		$scope = $propertyReflection->getScope();
		$inFunction = $scope->getFunction();
		if (
			$fetch instanceof PropertyFetch
			&& $fetch->var instanceof Node\Expr\Variable
			&& is_string($fetch->var->name)
			&& $fetch->var->name === 'this'
			&& $fetch->name instanceof Node\Identifier
			&& $inFunction instanceof PhpMethodFromParserNodeReflection
			&& $inFunction->isPropertyHook()
			&& $inFunction->getHookedPropertyName() === $fetch->name->toString()
		) {
			$propertyType = $propertyReflection->getReadableType();
		} else {
			$propertyType = $propertyReflection->getWritableType();
		}

		$assignedValueType = $scope->getType($assignedExpr);

		$accepts = $this->ruleLevelHelper->accepts($propertyType, $assignedValueType, $scope->isDeclareStrictTypes());
		if (!$accepts->result) {
			$propertyDescription = $this->describePropertyByName($propertyReflection, $propertyReflection->getName());
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($propertyType, $assignedValueType);

			return [
				RuleErrorBuilder::message(sprintf(
					'%s (%s) does not accept %s.',
					$propertyDescription,
					$propertyType->describe($verbosityLevel),
					$assignedValueType->describe($verbosityLevel),
				))
					->identifier('assign.propertyType')
					->acceptsReasonsTip($accepts->reasons)
					->build(),
			];
		}

		return [];
	}

	private function describePropertyByName(PropertyReflection $property, string $propertyName): string
	{
		if (!$property->isStatic()) {
			return sprintf('Property %s::$%s', $property->getDeclaringClass()->getDisplayName(), $propertyName);
		}

		return sprintf('Static property %s::$%s', $property->getDeclaringClass()->getDisplayName(), $propertyName);
	}

}
