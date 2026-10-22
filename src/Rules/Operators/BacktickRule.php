<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PhpParser\Node\Expr\ShellExec;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
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
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return ShellExec::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->deprecatesBacktickOperator()) {
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
