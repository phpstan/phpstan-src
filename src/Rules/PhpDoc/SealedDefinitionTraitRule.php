<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

/**
 * @implements Rule<Node\Stmt\Trait_>
 */
#[RegisteredRule(level: 0)]
final class SealedDefinitionTraitRule implements Rule
{

	public function __construct(
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
		if (
			$node->namespacedName === null
			|| !$this->reflectionProvider->hasClass($node->namespacedName->toString())
		) {
			return [];
		}

		$traitReflection = $this->reflectionProvider->getClass($node->namespacedName->toString());
		$sealedTags = $traitReflection->getSealedTags();

		if (count($sealedTags) === 0) {
			return [];
		}

		return [
			RuleErrorBuilder::message('PHPDoc tag @phpstan-sealed is only valid on class or interface.')
				->identifier('sealed.onTrait')
				->build(),
		];
	}

}
