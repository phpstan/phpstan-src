<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function strtolower;

/** @implements Rule<InClassMethodNode> */
#[RegisteredRule(level: 0)]
final class ConsistentConstructorDeclarationRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();
		if (strtolower($method->getName()) !== '__construct') {
			return [];
		}

		$classReflection = $node->getClassReflection();
		if (!$classReflection->hasConsistentConstructor()) {
			return [];
		}

		if ($classReflection->isFinal()) {
			return [];
		}

		if (!$method->isPrivate()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Private constructor cannot be enforced as consistent for child classes.')
				->identifier('consistentConstructor.private')
				->build(),
		];
	}

}
