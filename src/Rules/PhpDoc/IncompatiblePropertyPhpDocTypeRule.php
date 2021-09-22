<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\ClassPropertyNode>
 */
class IncompatiblePropertyPhpDocTypeRule implements Rule
{

	private \PHPStan\Rules\Generics\GenericObjectTypeCheck $genericObjectTypeCheck;

	private UnresolvableTypeHelper $unresolvableTypeHelper;

	public function __construct(
		GenericObjectTypeCheck $genericObjectTypeCheck,
		UnresolvableTypeHelper $unresolvableTypeHelper
	)
	{
		$this->genericObjectTypeCheck = $genericObjectTypeCheck;
		$this->unresolvableTypeHelper = $unresolvableTypeHelper;
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$propertyName = $node->getName();
		$propertyReflection = $scope->getClassReflection()->getNativeProperty($propertyName);

		if (!$propertyReflection->hasPhpDocType()) {
			return [];
		}

		$phpDocType = $propertyReflection->getPhpDocType();
		$description = 'PHPDoc tag @var';
		if ($propertyReflection->isPromoted()) {
			$description = 'PHPDoc type';
		}

		$messages = [];
		if (
			$this->unresolvableTypeHelper->containsUnresolvableType($phpDocType)
		) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s for property %s::$%s contains unresolvable type.',
				$description,
				$propertyReflection->getDeclaringClass()->getName(),
				$propertyName
			))->build();
		}

		$nativeType = $propertyReflection->getNativeType();
		$isSuperType = $nativeType->isSuperTypeOf($phpDocType);
		if ($isSuperType->no()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s for property %s::$%s with type %s is incompatible with native type %s.',
				$description,
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$propertyName,
				$phpDocType->describe(VerbosityLevel::typeOnly()),
				$nativeType->describe(VerbosityLevel::typeOnly())
			))->build();

		} elseif ($isSuperType->maybe()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s for property %s::$%s with type %s is not subtype of native type %s.',
				$description,
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$propertyName,
				$phpDocType->describe(VerbosityLevel::typeOnly()),
				$nativeType->describe(VerbosityLevel::typeOnly())
			))->build();
		}

		$className = SprintfHelper::escapeFormatString($propertyReflection->getDeclaringClass()->getDisplayName());
		$escapedPropertyName = SprintfHelper::escapeFormatString($propertyName);

		$messages = array_merge($messages, $this->genericObjectTypeCheck->check(
			$phpDocType,
			sprintf(
				'%s for property %s::$%s contains generic type %%s but class %%s is not generic.',
				$description,
				$className,
				$escapedPropertyName
			),
			sprintf(
				'Generic type %%s in %s for property %s::$%s does not specify all template types of class %%s: %%s',
				$description,
				$className,
				$escapedPropertyName
			),
			sprintf(
				'Generic type %%s in %s for property %s::$%s specifies %%d template types, but class %%s supports only %%d: %%s',
				$description,
				$className,
				$escapedPropertyName
			),
			sprintf(
				'Type %%s in generic type %%s in %s for property %s::$%s is not subtype of template type %%s of class %%s.',
				$description,
				$className,
				$escapedPropertyName
			)
		));

		return $messages;
	}

}
