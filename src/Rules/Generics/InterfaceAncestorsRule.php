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
class InterfaceAncestorsRule implements Rule
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
		if (!$originalNode instanceof Node\Stmt\Interface_) {
			return [];
		}
		$classReflection = $node->getClassReflection();

		$interfaceName = $classReflection->getName();
		$escapedInterfaceName = SprintfHelper::escapeFormatString($interfaceName);

		$extendsErrors = $this->genericAncestorsCheck->check(
			$originalNode->extends,
			array_map(static fn (ExtendsTag $tag): Type => $tag->getType(), $classReflection->getExtendsTags()),
			sprintf('Interface %s @extends tag contains incompatible type %%s.', $escapedInterfaceName),
			sprintf('Interface %s has @extends tag, but does not extend any interface.', $escapedInterfaceName),
			sprintf('The @extends tag of interface %s describes %%s but the interface extends: %%s', $escapedInterfaceName),
			'PHPDoc tag @extends contains generic type %s but %s %s is not generic.',
			'Generic type %s in PHPDoc tag @extends does not specify all template types of %s %s: %s',
			'Generic type %s in PHPDoc tag @extends specifies %d template types, but %s %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @extends is not subtype of template type %s of %s %s.',
			'PHPDoc tag @extends has invalid type %s.',
			sprintf('Interface %s extends generic interface %%s but does not specify its types: %%s', $escapedInterfaceName),
			sprintf('in extended type %%s of interface %s', $escapedInterfaceName),
		);

		$implementsErrors = $this->genericAncestorsCheck->check(
			[],
			array_map(static fn (ImplementsTag $tag): Type => $tag->getType(), $classReflection->getImplementsTags()),
			sprintf('Interface %s @implements tag contains incompatible type %%s.', $escapedInterfaceName),
			sprintf('Interface %s has @implements tag, but can not implement any interface, must extend from it.', $escapedInterfaceName),
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
		);

		foreach ($this->crossCheckInterfacesHelper->check($classReflection) as $error) {
			$implementsErrors[] = $error;
		}

		return array_merge($extendsErrors, $implementsErrors);
	}

}
