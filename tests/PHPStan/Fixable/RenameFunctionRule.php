<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function str_ends_with;

/**
 * Renames each function. The analysis reaches each function after the fixes
 * of the functions above it, so a function that a fix left marked as its own
 * origNode is reported.
 *
 * @implements Rule<Function_>
 */
final class RenameFunctionRule implements Rule
{

	public function getNodeType(): string
	{
		return Function_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->getAttribute('origNode') !== null) {
			return [
				RuleErrorBuilder::message('Function has an origNode attribute.')
					->identifier('tests.origNode')
					->build(),
			];
		}

		if (str_ends_with($node->name->toString(), 'Renamed')) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Renaming function.')
				->identifier('tests.renameFunction')
				->fixNode($node, static function (Function_ $function): Function_ {
					$function->name = new Identifier($function->name->toString() . 'Renamed');

					return $function;
				})
				->build(),
		];
	}

}
