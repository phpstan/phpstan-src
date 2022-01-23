<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
class ExistingClassesInPropertiesRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private PhpVersion $phpVersion,
		private bool $checkClassCaseSensitivity,
		private bool $checkThisOnly,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$propertyReflection = $scope->getClassReflection()->getNativeProperty($node->getName());
		if ($this->checkThisOnly) {
			$referencedClasses = $propertyReflection->getNativeType()->getReferencedClasses();
		} else {
			$referencedClasses = array_merge(
				$propertyReflection->getNativeType()->getReferencedClasses(),
				$propertyReflection->getPhpDocType()->getReferencedClasses(),
			);
		}

		$errors = [];
		foreach ($referencedClasses as $referencedClass) {
			if ($this->reflectionProvider->hasClass($referencedClass)) {
				if ($this->reflectionProvider->getClass($referencedClass)->isTrait()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Property %s::$%s has invalid type %s.',
						$propertyReflection->getDeclaringClass()->getDisplayName(),
						$node->getName(),
						$referencedClass,
					))->build();
				}
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s has unknown class %s as its type.',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
				$referencedClass,
			))->discoveringSymbolsTip()->build();
		}

		if ($this->checkClassCaseSensitivity) {
			$errors = array_merge(
				$errors,
				$this->classCaseSensitivityCheck->checkClassNames(array_map(static fn (string $class): ClassNameNodePair => new ClassNameNodePair($class, $node), $referencedClasses)),
			);
		}

		if (
			$this->phpVersion->supportsPureIntersectionTypes()
			&& $this->unresolvableTypeHelper->containsUnresolvableType($propertyReflection->getNativeType())
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s has unresolvable native type.',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
			))->build();
		}

		return $errors;
	}

}
