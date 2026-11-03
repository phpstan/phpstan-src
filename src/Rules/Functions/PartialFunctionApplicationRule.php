<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\PartialFunctionApplicationNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<PartialFunctionApplicationNode>
 */
#[RegisteredRule(level: 0)]
final class PartialFunctionApplicationRule implements Rule
{

	public function getNodeType(): string
	{
		return PartialFunctionApplicationNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalNode = $node->getOriginalNode();
		if ($originalNode instanceof Node\Expr\New_) {
			return [
				RuleErrorBuilder::message('Cannot create callable from the new operator.')
					->identifier('callable.notSupported')
					->nonIgnorable()
					->build(),
			];
		}

		if ($originalNode instanceof Node\Expr\NullsafeMethodCall) {
			return [
				RuleErrorBuilder::message('Cannot combine nullsafe operator with callable creation.')
					->identifier('callable.notSupported')
					->nonIgnorable()
					->build(),
			];
		}

		if (!$scope->getPhpVersion()->supportsPartialFunctionApplication()->yes()) {
			return [
				RuleErrorBuilder::message('Partial function application is supported only on PHP 8.6 and later.')
					->identifier('callable.notSupported')
					->nonIgnorable()
					->build(),
			];
		}

		return [];
	}

}
