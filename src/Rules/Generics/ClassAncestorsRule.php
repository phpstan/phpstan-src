<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassNode;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;
use function array_map;
use function array_merge;
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

		$extendsErrors = $this->genericAncestorsCheck->check(
			$originalNode->extends !== null ? [$originalNode->extends] : [],
			array_map(static fn (ExtendsTag $tag): Type => $tag->getType(), $classReflection->getExtendsTags()),
			sprintf('Class %s @extends tag contains incompatible type %%s.', $escapedClassName),
			sprintf('Class %s has @extends tag, but does not extend any class.', $escapedClassName),
			sprintf('The @extends tag of class %s describes %%s but the class extends %%s.', $escapedClassName),
			'PHPDoc tag @extends contains generic type %s but %s %s is not generic.',
			'Generic type %s in PHPDoc tag @extends does not specify all template types of %s %s: %s',
			'Generic type %s in PHPDoc tag @extends specifies %d template types, but %s %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @extends is not subtype of template type %s of %s %s.',
			'PHPDoc tag @extends has invalid type %s.',
			sprintf('Class %s extends generic class %%s but does not specify its types: %%s', $escapedClassName),
			sprintf('in extended type %%s of class %s', $escapedClassName),
		);

		$implementsErrors = $this->genericAncestorsCheck->check(
			$originalNode->implements,
			array_map(static fn (ImplementsTag $tag): Type => $tag->getType(), $classReflection->getImplementsTags()),
			sprintf('Class %s @implements tag contains incompatible type %%s.', $escapedClassName),
			sprintf('Class %s has @implements tag, but does not implement any interface.', $escapedClassName),
			sprintf('The @implements tag of class %s describes %%s but the class implements: %%s', $escapedClassName),
			'PHPDoc tag @implements contains generic type %s but %s %s is not generic.',
			'Generic type %s in PHPDoc tag @implements does not specify all template types of %s %s: %s',
			'Generic type %s in PHPDoc tag @implements specifies %d template types, but %s %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @implements is not subtype of template type %s of %s %s.',
			'PHPDoc tag @implements has invalid type %s.',
			sprintf('Class %s implements generic interface %%s but does not specify its types: %%s', $escapedClassName),
			sprintf('in implemented type %%s of class %s', $escapedClassName),
		);

		foreach ($this->crossCheckInterfacesHelper->check($classReflection) as $error) {
			$implementsErrors[] = $error;
		}

		return array_merge($extendsErrors, $implementsErrors);
	}

}
