<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
#[RegisteredRule(level: 0)]
final class ExistingClassesInPropertiesRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassNameCheck $classCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private PhpVersion $phpVersion,
		#[AutowiredParameter]
		private bool $checkClassCaseSensitivity,
		#[AutowiredParameter]
		private bool $checkThisOnly,
		#[AutowiredParameter(ref: '%tips.discoveringSymbols%')]
		private bool $discoveringSymbolsTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$propertyReflection = $node->getClassReflection()->getNativeProperty($node->getName());
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
					))->identifier('property.trait')->build();
				}
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s has unknown class %s as its type.',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
				$referencedClass,
			))
				->identifier('class.notFound');

			if ($this->discoveringSymbolsTip) {
				$errorBuilder->discoveringSymbolsTip();
			}

			$errors[] = $errorBuilder->build();
		}

		$errors = array_merge(
			$errors,
			$this->classCheck->checkClassNames(
				$scope,
				array_map(static fn (string $class): ClassNameNodePair => new ClassNameNodePair($class, $node), $referencedClasses),
				ClassNameUsageLocation::from(ClassNameUsageLocation::PROPERTY_TYPE, [
					'property' => $propertyReflection,
				]),
				$this->checkClassCaseSensitivity,
			),
		);

		if (
			$this->phpVersion->supportsPureIntersectionTypes()
			&& $this->unresolvableTypeHelper->containsUnresolvableType($propertyReflection->getNativeType())
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s has unresolvable native type.',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
			))->identifier('property.unresolvableNativeType')->build();
		}

		return $errors;
	}

}
