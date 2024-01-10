<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

/**
 * @implements Rule<InClassNode>
 */
class RequireImplementsDefinitionClassRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$implementsTags = $classReflection->getRequireImplementsTags();

		if (count($implementsTags) === 0) {
			return [];
		}

		return [
			RuleErrorBuilder::message('PHPDoc tag @phpstan-require-implements is only valid on trait.')->build(),
		];
	}

}
