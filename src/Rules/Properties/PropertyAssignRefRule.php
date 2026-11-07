<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Expr\AssignRef>
 */
#[RegisteredRule(level: 0)]
final class PropertyAssignRefRule implements Rule
{

	public function __construct(
		private PropertyReflectionFinder $propertyReflectionFinder,
		#[AutowiredParameter(ref: '%featureToggles.checkWholePhpVersionRange%')]
		private bool $checkWholePhpVersionRange,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\AssignRef::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$supportsAsymmetricVisibility = $scope->getPhpVersion()->supportsAsymmetricVisibility();
		if ($this->checkWholePhpVersionRange ? $supportsAsymmetricVisibility->no() : !$supportsAsymmetricVisibility->yes()) {
			return [];
		}

		if (!$node->expr instanceof Node\Expr\PropertyFetch) {
			return [];
		}

		$propertyFetch = $node->expr;

		$errors = [];
		$reflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($propertyFetch, $scope);
		foreach ($reflections as $propertyReflection) {
			$nativeReflection = $propertyReflection->getNativeReflection();
			if ($nativeReflection === null) {
				continue;
			}
			if ($scope->canWriteProperty($propertyReflection)) {
				continue;
			}

			$declaringClass = $nativeReflection->getDeclaringClass();
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s with %s visibility is assigned by reference.',
				$declaringClass->getDisplayName(),
				$propertyReflection->getName(),
				$propertyReflection->isPrivateSet() ? 'private(set)' : (
					$propertyReflection->isProtectedSet() ? 'protected(set)' : (
						$propertyReflection->isPrivate() ? 'private' : 'protected'
					)
				),
			))
				->identifier('property.assignByRef')
				->build();
		}

		return $errors;
	}

}
