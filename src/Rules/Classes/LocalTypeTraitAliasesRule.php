<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Rule<Node\Stmt\Trait_>
 */
#[RegisteredRule(level: 0)]
#[ValidatesStubFiles]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/LocalTypeTraitAliasesRule.cpp')]
final class LocalTypeTraitAliasesRule implements Rule
{

	public function __construct(private LocalTypeAliasesCheck $check, private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Trait_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$traitName = $node->namespacedName;
		if ($traitName === null) {
			return [];
		}

		if (!$this->reflectionProvider->hasClass($traitName->toString())) {
			return [];
		}

		return $this->check->checkInTraitDefinitionContext($this->reflectionProvider->getClass($traitName->toString()));
	}

}
