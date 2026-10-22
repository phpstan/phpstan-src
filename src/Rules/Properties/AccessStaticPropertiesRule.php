<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Expr\StaticPropertyFetch>
 */
#[RegisteredRule(level: 0)]
final class AccessStaticPropertiesRule implements Rule
{

	public function __construct(private AccessStaticPropertiesCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return StaticPropertyFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->check->check($node, $scope, false);
	}

}
