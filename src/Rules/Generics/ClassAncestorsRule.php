<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassNode;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use function array_map;
use function array_merge;
use function count;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class ClassAncestorsRule implements Rule
{

	public function __construct(
		private GenericAncestorsCheck $genericAncestorsCheck,
		private CrossCheckInterfacesHelper $crossCheckInterfacesHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalNode = $node->getOriginalNode();
		if (!$originalNode instanceof Node\Stmt\Class_) {
			return [];
		}
		$classReflection = $node->getClassReflection();
		if ($classReflection->isAnonymous()) {
			return [];
		}
		$className = $classReflection->getName();
		$escapedClassName = SprintfHelper::escapeFormatString($className);

		$extendsTagTypes = array_map(static fn (ExtendsTag $tag): Type => $tag->getType(), $classReflection->getExtendsTags());
		$implementsTagTypes = array_map(static fn (ImplementsTag $tag): Type => $tag->getType(), $classReflection->getImplementsTags());

		$extendsErrors = $this->genericAncestorsCheck->check(
			$originalNode->extends !== null ? [$originalNode->extends] : [],
			$extendsTagTypes,
			sprintf('Class %s @extends tag contains incompatible type %%s.', $escapedClassName),
			sprintf('Class %s has @extends tag, but does not extend any class.', $escapedClassName),
			sprintf('The @extends tag of class %s describes %%s but the class extends %%s.', $escapedClassName),
			'PHPDoc tag @extends contains generic type %s but %s %s is not generic.',
			'Generic type %s in PHPDoc tag @extends does not specify all template types of %s %s: %s',
			'Generic type %s in PHPDoc tag @extends specifies %d template types, but %s %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @extends is not subtype of template type %s of %s %s.',
			'Call-site variance annotation of %s in generic type %s in PHPDoc tag @extends is not allowed.',
			'PHPDoc tag @extends has invalid type %s.',
			sprintf('Class %s extends generic class %%s but does not specify its types: %%s', $escapedClassName),
			sprintf('in extended type %%s of class %s', $escapedClassName),
		);

		$implementsErrors = $this->genericAncestorsCheck->check(
			$originalNode->implements,
			$implementsTagTypes,
			sprintf('Class %s @implements tag contains incompatible type %%s.', $escapedClassName),
			sprintf('Class %s has @implements tag, but does not implement any interface.', $escapedClassName),
			sprintf('The @implements tag of class %s describes %%s but the class implements: %%s', $escapedClassName),
			'PHPDoc tag @implements contains generic type %s but %s %s is not generic.',
			'Generic type %s in PHPDoc tag @implements does not specify all template types of %s %s: %s',
			'Generic type %s in PHPDoc tag @implements specifies %d template types, but %s %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @implements is not subtype of template type %s of %s %s.',
			'Call-site variance annotation of %s in generic type %s in PHPDoc tag @implements is not allowed.',
			'PHPDoc tag @implements has invalid type %s.',
			sprintf('Class %s implements generic interface %%s but does not specify its types: %%s', $escapedClassName),
			sprintf('in implemented type %%s of class %s', $escapedClassName),
		);

		foreach ($this->crossCheckInterfacesHelper->check($classReflection) as $error) {
			$implementsErrors[] = $error;
		}

		foreach ($this->checkConsistentTemplateViolations($classReflection, $extendsTagTypes, 'extends') as $error) {
			$extendsErrors[] = $error;
		}

		foreach ($this->checkConsistentTemplateViolations($classReflection, $implementsTagTypes, 'implements') as $error) {
			$implementsErrors[] = $error;
		}

		return array_merge($extendsErrors, $implementsErrors);
	}

	/**
	 * @param Type[] $types
	 *
	 * @return RuleError[]
	 */
	private function checkConsistentTemplateViolations(ClassReflection $classReflection, array $types, string $tagName): array
	{
		/** @var RuleError[] $errors */
		$errors = [];

		foreach ($types as $type) {
			if (! $type instanceof GenericObjectType) {
				continue;
			}

			$ancestorClassReflection = $type->getClassReflection();

			if ($ancestorClassReflection === null) {
				continue;
			}

			if (! $ancestorClassReflection->hasConsistentTemplates()) {
				continue;
			}

			$className = $classReflection->getName();
			$escapedClassName = SprintfHelper::escapeFormatString($className);

			$ancestorTemplateCount = count($ancestorClassReflection->getTemplateTags());

			if (count($classReflection->getTemplateTags()) !== $ancestorTemplateCount) {
				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Class %s should have same amount of template tags as %s. %d expected but %d found.',
						$escapedClassName,
						SprintfHelper::escapeFormatString($ancestorClassReflection->getName()),
						$ancestorTemplateCount,
						count($classReflection->getTemplateTags()),
					),
				)->build();
			}

			foreach ($type->getTypes() as $extendType) {
				if ($extendType instanceof TemplateType) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Class %s has non-template types in @%s tag but %s has consistent templates.',
						$escapedClassName,
						$tagName,
						SprintfHelper::escapeFormatString($ancestorClassReflection->getName()),
					),
				)->build();
			}
		}

		return $errors;
	}

}
