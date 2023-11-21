<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\DeclarePositionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Stmt>
 */
class DeclarePositionRule implements Rule
{

	public function getNodeType(): string
	{
		return Stmt\Declare_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->hasAttribute(DeclarePositionVisitor::ATTRIBUTE_NAME)) {
			return [];
		}

		$isFirstStatement = (bool) $node->getAttribute(DeclarePositionVisitor::ATTRIBUTE_NAME);
		if ($isFirstStatement) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Declare must be the very first statement.',
			))->nonIgnorable()->build(),
		];
	}

}
