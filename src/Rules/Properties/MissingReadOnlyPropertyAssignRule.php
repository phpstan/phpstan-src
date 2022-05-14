<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use ReflectionException;
use function array_key_exists;
use function explode;
use function sprintf;

/**
 * @implements Rule<ClassPropertiesNode>
 */
class MissingReadOnlyPropertyAssignRule implements Rule
{

	/** @var array<string, string[]> */
	private array $additionalConstructorsCache = [];

	/**
	 * @param string[] $additionalConstructors
	 */
	public function __construct(
		private array $additionalConstructors,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertiesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}
		$classReflection = $scope->getClassReflection();
		[$properties, $prematureAccess, $additionalAssigns] = $node->getUninitializedProperties($scope, $this->getConstructors($classReflection), []);

		$errors = [];
		foreach ($properties as $propertyName => $propertyNode) {
			if (!$propertyNode->isReadOnly() && !$propertyNode->isReadonlyByPhpDoc()) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Class %s has an uninitialized readonly property $%s. Assign it in the constructor.',
				$classReflection->getDisplayName(),
				$propertyName,
			))->line($propertyNode->getLine())->build();
		}

		foreach ($prematureAccess as [$propertyName, $line, $propertyNode]) {
			if (!$propertyNode->isReadOnly() && !$propertyNode->isReadonlyByPhpDoc()) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Access to an uninitialized readonly property %s::$%s.',
				$classReflection->getDisplayName(),
				$propertyName,
			))->line($line)->build();
		}

		foreach ($additionalAssigns as [$propertyName, $line, $propertyNode]) {
			if (!$propertyNode->isReadOnly() && !$propertyNode->isReadonlyByPhpDoc()) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Readonly property %s::$%s is already assigned.',
				$classReflection->getDisplayName(),
				$propertyName,
			))->line($line)->build();
		}

		return $errors;
	}

	/**
	 * @return string[]
	 */
	private function getConstructors(ClassReflection $classReflection): array
	{
		if (array_key_exists($classReflection->getName(), $this->additionalConstructorsCache)) {
			return $this->additionalConstructorsCache[$classReflection->getName()];
		}
		$constructors = [];
		if ($classReflection->hasConstructor()) {
			$constructors[] = $classReflection->getConstructor()->getName();
		}

		$nativeReflection = $classReflection->getNativeReflection();
		foreach ($this->additionalConstructors as $additionalConstructor) {
			[$className, $methodName] = explode('::', $additionalConstructor);
			if (!$nativeReflection->hasMethod($methodName)) {
				continue;
			}
			$nativeMethod = $nativeReflection->getMethod($methodName);
			if ($nativeMethod->getDeclaringClass()->getName() !== $nativeReflection->getName()) {
				continue;
			}

			try {
				$prototype = $nativeMethod->getPrototype();
			} catch (ReflectionException) {
				$prototype = $nativeMethod;
			}

			if ($prototype->getDeclaringClass()->getName() !== $className) {
				continue;
			}

			$constructors[] = $methodName;
		}

		$this->additionalConstructorsCache[$classReflection->getName()] = $constructors;

		return $constructors;
	}

}
