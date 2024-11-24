<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\Catch_>
 */
final class NoncapturingCatchRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Catch_::class;
	}

	/**
	 * @param Node\Stmt\Catch_ $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getPhpVersion()->supportsNoncapturingCatches()->yes()) {
			return [];
		}

		if ($node->var !== null) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Non-capturing catch is supported only on PHP 8.0 and later.')
				->nonIgnorable()
				->identifier('catch.nonCapturingNotSupported')
				->build(),
		];
	}

}
