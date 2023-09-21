<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_reverse;
use function implode;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Echo_>
 */
class ParentStmtTypesRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Echo_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message(sprintf(
				'Parents: %s',
				implode(', ', array_reverse($node->getAttribute('parentStmtTypes'))),
			))->identifier('tests.parentStmtTypes')->build(),
		];
	}

}
