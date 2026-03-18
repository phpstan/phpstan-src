<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use function strtolower;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 0)]
final class DuplicateDeclarationRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();

		return DuplicateDeclarationHelper::checkClassLike(
			$node->getOriginalNode(),
			$classReflection->getDisplayName(),
			strtolower($classReflection->getClassTypeDescription()),
		);
	}

}
