<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InTraitNode;
use PHPStan\Rules\Rule;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Rule<InTraitNode>
 */
#[RegisteredRule(level: 0)]
#[ValidatesStubFiles]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/LocalTypeTraitUseAliasesRule.cpp')]
final class LocalTypeTraitUseAliasesRule implements Rule
{

	public function __construct(private LocalTypeAliasesCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return InTraitNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->check->checkInTraitUseContext(
			$scope,
			$node->getTraitReflection(),
			$node->getImplementingClassReflection(),
			$node->getOriginalNode(),
		);
	}

}
