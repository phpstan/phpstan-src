<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\Expr\CloneReinitializationExpr;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\TypeUtils;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<PropertyAssignNode>
 */
#[RegisteredRule(level: 3)]
final class ReadOnlyPropertyAssignRule implements Rule
{

	public function __construct(
		private PropertyReflectionFinder $propertyReflectionFinder,
		private ConstructorsHelper $constructorsHelper,
		private PhpVersion $phpVersion,
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
		if (!$propertyFetch instanceof Node\Expr\PropertyFetch) {
			return [];
		}

		$inCloneWith = (bool) $propertyFetch->getAttribute('inCloneWith', false);
		if ($inCloneWith) {
			return [];
		}

		$errors = [];
		$reflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($propertyFetch, $scope);
		foreach ($reflections as $propertyReflection) {
			$nativeReflection = $propertyReflection->getNativeReflection();
			if ($nativeReflection === null) {
				continue;
			}
			if (!$scope->canWriteProperty($propertyReflection)) {
				continue;
			}
			if (!$nativeReflection->isReadOnly()) {
				continue;
			}

			$declaringClass = $nativeReflection->getDeclaringClass();

			if (!$scope->isInClass()) {
				$errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is assigned outside of its declaring class.', $declaringClass->getDisplayName(), $propertyReflection->getName()))
					->line($propertyFetch->name->getStartLine())
					->identifier('property.readOnlyAssignOutOfClass')
					->build();
				continue;
			}

			$scopeClassReflection = $scope->getClassReflection();
			if ($scopeClassReflection->getName() !== $declaringClass->getName()) {
				$allowedInSubclass = $this->phpVersion->supportsAsymmetricVisibility()
					&& !$propertyReflection->isPrivateSet()
					&& $scopeClassReflection->isSubclassOfClass($propertyReflection->getDeclaringClass());
				if (!$allowedInSubclass) {
					$errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is assigned outside of its declaring class.', $declaringClass->getDisplayName(), $propertyReflection->getName()))
						->line($propertyFetch->name->getStartLine())
						->identifier('property.readOnlyAssignOutOfClass')
						->build();
					continue;
				}
			}

			$scopeMethod = $scope->getFunction();
			if (!$scopeMethod instanceof MethodReflection) {
				throw new ShouldNotHappenException();
			}

			$methodName = $scopeMethod->getName();
			$inClone = $this->phpVersion->supportsReadonlyPropertyReinitializationOnClone() && strtolower($methodName) === '__clone';
			if (
				in_array($methodName, $this->constructorsHelper->getConstructors($scopeClassReflection), true)
				|| strtolower($methodName) === '__unserialize'
				|| $inClone
			) {
				if (TypeUtils::findThisType($scope->getType($propertyFetch->var)) === null) {
					$errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is not assigned on $this.', $declaringClass->getDisplayName(), $propertyReflection->getName()))
						->line($propertyFetch->name->getStartLine())
						->identifier('property.readOnlyAssignNotOnThis')
						->build();
				} elseif (
					$inClone
					&& !$scope->hasExpressionType(new CloneReinitializationExpr($propertyReflection->getName()))->no()
				) {
					$errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is already assigned.', $declaringClass->getDisplayName(), $propertyReflection->getName()))
						->line($propertyFetch->name->getStartLine())
						->identifier('assign.readOnlyProperty')
						->build();
				}

				continue;
			}

			if ($node->isArrayAccessOffsetWrite($scope)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is assigned outside of the constructor.', $declaringClass->getDisplayName(), $propertyReflection->getName()))
				->line($propertyFetch->name->getStartLine())
				->identifier('property.readOnlyAssignNotInConstructor')
				->build();
		}

		return $errors;
	}

}
