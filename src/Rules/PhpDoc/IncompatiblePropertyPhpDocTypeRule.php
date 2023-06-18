<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
class IncompatiblePropertyPhpDocTypeRule implements Rule
{

	public function __construct(
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
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

		$propertyName = $node->getName();
		$phpDocType = $node->getPhpDocType();
		if ($phpDocType === null) {
			return [];
		}

		$description = 'PHPDoc tag @var';
		if ($node->isPromoted()) {
			$description = 'PHPDoc type';
		}

		$messages = [];
		if (
			$this->unresolvableTypeHelper->containsUnresolvableType($phpDocType)
		) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s for property %s::$%s contains unresolvable type.',
				$description,
				$scope->getClassReflection()->getDisplayName(),
				$propertyName,
			))->build();
		}

		$nativeType = ParserNodeTypeToPHPStanType::resolve($node->getNativeType(), $scope->getClassReflection());
		$isSuperType = $nativeType->isSuperTypeOf($phpDocType);
		if ($isSuperType->no()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s for property %s::$%s with type %s is incompatible with native type %s.',
				$description,
				$scope->getClassReflection()->getDisplayName(),
				$propertyName,
				$phpDocType->describe(VerbosityLevel::typeOnly()),
				$nativeType->describe(VerbosityLevel::typeOnly()),
			))->build();

		} elseif ($isSuperType->maybe()) {
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'%s for property %s::$%s with type %s is not subtype of native type %s.',
				$description,
				$scope->getClassReflection()->getDisplayName(),
				$propertyName,
				$phpDocType->describe(VerbosityLevel::typeOnly()),
				$nativeType->describe(VerbosityLevel::typeOnly()),
			));

			if ($phpDocType instanceof TemplateType) {
				$errorBuilder->tip(sprintf('Write @template %s of %s to fix this.', $phpDocType->getName(), $nativeType->describe(VerbosityLevel::typeOnly())));
			}

			$messages[] = $errorBuilder->build();
		}

		$className = SprintfHelper::escapeFormatString($scope->getClassReflection()->getDisplayName());
		$escapedPropertyName = SprintfHelper::escapeFormatString($propertyName);

		$messages = array_merge($messages, $this->genericObjectTypeCheck->check(
			$phpDocType,
			sprintf(
				'%s for property %s::$%s contains generic type %%s but %%s %%s is not generic.',
				$description,
				$className,
				$escapedPropertyName,
			),
			sprintf(
				'Generic type %%s in %s for property %s::$%s does not specify all template types of %%s %%s: %%s',
				$description,
				$className,
				$escapedPropertyName,
			),
			sprintf(
				'Generic type %%s in %s for property %s::$%s specifies %%d template types, but %%s %%s supports only %%d: %%s',
				$description,
				$className,
				$escapedPropertyName,
			),
			sprintf(
				'Type %%s in generic type %%s in %s for property %s::$%s is not subtype of template type %%s of %%s %%s.',
				$description,
				$className,
				$escapedPropertyName,
			),
			sprintf(
				'Type projection %%s in generic type %%s in %s for property %s::$%s is conflicting with variance of template type %%s of %%s %%s.',
				$description,
				$className,
				$escapedPropertyName,
			),
			sprintf(
				'Type projection %%s in generic type %%s in %s for property %s::$%s is redundant, template type %%s of %%s %%s has the same variance.',
				$description,
				$className,
				$escapedPropertyName,
			),
		));

		return $messages;
	}

}
