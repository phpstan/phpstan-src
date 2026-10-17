<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Rule<Node\Expr\PropertyFetch>
 */
#[RegisteredRule(level: 0)]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/AccessPropertiesRule.cpp')]
final class AccessPropertiesRule implements Rule
{

	public function __construct(private AccessPropertiesCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return PropertyFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->check->check($node, $scope, false);
	}

}
