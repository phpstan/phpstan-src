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
class UninitializedPropertyRule implements Rule
{

	private ReadWritePropertiesExtensionProvider $extensionProvider;

	/** @var string[] */
	private array $additionalConstructors;

	/** @var array<string, string[]> */
	private array $additionalConstructorsCache = [];

	/**
	 * @param string[] $additionalConstructors
	 */
	public function __construct(
		ReadWritePropertiesExtensionProvider $extensionProvider,
		array $additionalConstructors,
	)
	{
		$this->extensionProvider = $extensionProvider;
		$this->additionalConstructors = $additionalConstructors;
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
		[$properties, $prematureAccess] = $node->getUninitializedProperties($scope, $this->getConstructors($classReflection), $this->extensionProvider->getExtensions());

		$errors = [];
		foreach ($properties as $propertyName => $propertyNode) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Class %s has an uninitialized property $%s. Give it default value or assign it in the constructor.',
				$classReflection->getDisplayName(),
				$propertyName,
			))->line($propertyNode->getLine())->build();
		}

		foreach ($prematureAccess as [$propertyName, $line]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Access to an uninitialized property %s::$%s.',
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
			} catch (ReflectionException $e) {
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
