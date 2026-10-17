<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 2)]
#[ValidatesStubFiles]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/PropertyTagRule.cpp')]
final class PropertyTagRule implements Rule
{

	public function __construct(private PropertyTagCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->check->check($scope, $node->getClassReflection(), $node->getOriginalNode());
	}

}
