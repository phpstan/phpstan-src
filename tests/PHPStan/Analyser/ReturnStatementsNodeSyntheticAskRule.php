<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * Asks the type of a rule-built synthetic node from a ReturnStatementsNode
 * callback - the ImpossibleCheckTypeHelper-style pattern. The suspended fiber
 * must be resumed at the body boundary for the error to surface.
 *
 * @implements Rule<ReturnStatementsNode>
 */
class ReturnStatementsNodeSyntheticAskRule implements Rule
{

	public function getNodeType(): string
	{
		return ReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof MethodReturnStatementsNode && !$node instanceof FunctionReturnStatementsNode) {
			return [];
		}

		$synthetic = new FuncCall(new Name('strlen'), [new Arg(new String_('abc'))]);
		$type = $scope->getType($synthetic);

		return [
			RuleErrorBuilder::message(sprintf(
				'%s: %s',
				$node instanceof MethodReturnStatementsNode ? 'method' : 'function',
				$type->describe(VerbosityLevel::precise()),
			))->identifier('tests.syntheticAsk')->build(),
		];
	}

}
