<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Stmt\Trait_>
 */
final class RequireExtendsDefinitionTraitRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RequireExtendsCheck $requireExtendsCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Trait_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			$node->namespacedName === null
			|| !$this->reflectionProvider->hasClass($node->namespacedName->toString())
		) {
			return [];
		}

		$traitReflection = $this->reflectionProvider->getClass($node->namespacedName->toString());
		$extendsTags = $traitReflection->getRequireExtendsTags();

		return $this->requireExtendsCheck->checkExtendsTags($node, $extendsTags);
	}

}
