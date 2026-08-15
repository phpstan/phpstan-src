<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Node\BooleanOrNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * Asks the type of the virtual node itself - no handler supports it, so the
 * ask must degrade to mixed like MutatingScope::resolveType()'s fallback
 * instead of crashing the on-demand walk with "Unhandled expr".
 *
 * @implements Rule<BooleanOrNode>
 */
class VirtualNodeGetTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return BooleanOrNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message($scope->getType($node)->describe(VerbosityLevel::precise()))
				->identifier('tests.virtualNodeGetType')
				->build(),
		];
	}

}
