<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PhpParser\Node\Expr\ShellExec;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

/**
 * @implements Rule<ShellExec>
 */
#[RegisteredRule(level: 0)]
final class BacktickRule implements Rule
{

	public function __construct(
		#[AutowiredParameter(ref: '%featureToggles.checkWholePhpVersionRange%')]
		private bool $checkWholePhpVersionRange,
	)
	{
	}

	public function getNodeType(): string
	{
		return ShellExec::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$deprecatesBacktickOperator = $scope->getPhpVersion()->deprecatesBacktickOperator();
		if ($this->checkWholePhpVersionRange ? $deprecatesBacktickOperator->no() : !$deprecatesBacktickOperator->yes()) {
			return [];
		}

		$argExpr = null;
		foreach ($node->parts as $part) {
			if ($part instanceof Node\InterpolatedStringPart) {
				$expr = new Node\Scalar\String_($part->value);
			} else {
				$expr = $part;
			}

			if ($argExpr === null) {
				$argExpr = $expr;
				continue;
			}

			$argExpr = new Node\Expr\BinaryOp\Concat($argExpr, $expr);
		}

		if ($argExpr === null) {
			throw new ShouldNotHappenException();
		}

		return [
			RuleErrorBuilder::message('Backtick operator is deprecated in PHP 8.5. Use shell_exec() function call instead.')
				->identifier('backtick.deprecated')
				->fixNode($node, static fn () => new Node\Expr\FuncCall(new Node\Name('shell_exec'), [
					new Node\Arg($argExpr),
				]))
				->build(),
		];
	}

}
