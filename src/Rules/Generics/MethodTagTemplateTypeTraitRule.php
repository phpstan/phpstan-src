<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Stmt\Trait_>
 */
#[RegisteredRule(level: 2)]
final class MethodTagTemplateTypeTraitRule implements Rule
{

	public function __construct(
		private MethodTagTemplateTypeCheck $check,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Trait_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$traitName = $node->namespacedName;
		if ($traitName === null) {
			return [];
		}

		if (!$this->reflectionProvider->hasClass($traitName->toString())) {
			return [];
		}

		return $this->check->check(
			$this->reflectionProvider->getClass($traitName->toString()),
			$scope,
			$node,
			$docComment->getText(),
		);
	}

}
