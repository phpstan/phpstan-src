<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Replaces the imported RenameUseOther\Foo with a fully qualified RenameUseOther\Bar.
 *
 * @implements Rule<Node\Stmt\Use_>
 */
final class RenameUseRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Use_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->uses[0]->name->toString() !== 'RenameUseOther\Foo') {
			return [];
		}

		return [
			RuleErrorBuilder::message('Import RenameUseOther\Bar.')
				->identifier('tests.renameUse')
				->fixNode($node, static function (Node\Stmt\Use_ $use): Node\Stmt\Use_ {
					$use->uses[0]->name = new FullyQualified('RenameUseOther\Bar');
					return $use;
				})
				->build(),
		];
	}

}
