<?php declare(strict_types = 1);

namespace FixE2E;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Renames step1() to step2() and so on until step4(), so a fix leads to another fixable error.
 *
 * @implements Rule<FuncCall>
 */
final class StepRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name || preg_match('~^step([1-3])$~', $node->name->toString(), $matches) !== 1) {
			return [];
		}

		$next = 'step' . ((int) $matches[1] + 1);

		return [
			RuleErrorBuilder::message(sprintf('Call %s() instead.', $next))
				->identifier('fixE2E.step')
				->fixNode($node, static function (FuncCall $node) use ($next): FuncCall {
					$node->name = new Node\Name($next);
					return $node;
				})
				->build(),
		];
	}

}

/**
 * Removes every argument of noArgs() with its own error. The fixes of one call conflict, so one is applied per round.
 *
 * @implements Rule<FuncCall>
 */
final class NoArgsRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name || $node->name->toString() !== 'noArgs') {
			return [];
		}

		$errors = [];
		foreach ($node->getArgs() as $i => $arg) {
			$errors[] = RuleErrorBuilder::message(sprintf('Remove argument #%d.', $i + 1))
				->identifier('fixE2E.noArgs')
				->removeNode($arg)
				->build();
		}

		return $errors;
	}

}

/**
 * Renames ping() to pong() and back, so fixing never ends.
 *
 * @implements Rule<FuncCall>
 */
final class PingPongRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name || !in_array($node->name->toString(), ['ping', 'pong'], true)) {
			return [];
		}

		$other = $node->name->toString() === 'ping' ? 'pong' : 'ping';

		return [
			RuleErrorBuilder::message(sprintf('Call %s() instead.', $other))
				->identifier('fixE2E.pingPong')
				->fixNode($node, static function (FuncCall $node) use ($other): FuncCall {
					$node->name = new Node\Name($other);
					return $node;
				})
				->build(),
		];
	}

}
