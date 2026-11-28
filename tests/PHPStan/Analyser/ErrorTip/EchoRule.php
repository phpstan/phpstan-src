<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ErrorTip;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Echo_>
 */
final class EchoRule implements Rule
{

	public function getNodeType(): string
	{
		return Echo_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$expr = $node->exprs[0];
		if (!$expr instanceof String_) {
			return [];
		}

		$builder = RuleErrorBuilder::message($expr->value)->identifier('tests.echo');
		if ($expr->value === 'tip') {
			$builder->tip('Rule tip.');
		} elseif ($expr->value === 'tips') {
			$builder->addTip('Rule tip 1.')->addTip('Rule tip 2.');
		} elseif ($expr->value === 'nonIgnorable') {
			$builder->nonIgnorable();
		}

		return [$builder->build()];
	}

}
