<?php declare(strict_types = 1);

namespace FixIgnoredEntriesE2E;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Renames legacy() to modern(). A call with arguments is reported too, but cannot be fixed.
 *
 * @implements Rule<FuncCall>
 */
final class RenameRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name || $node->name->toString() !== 'legacy') {
			return [];
		}

		$error = RuleErrorBuilder::message('Call modern() instead.')->identifier('fixIgnoredEntriesE2E.rename');
		if ($node->getArgs() === []) {
			$error->fixNode($node, static function (FuncCall $node): FuncCall {
				$node->name = new Node\Name('modern');
				return $node;
			});
		}

		return [$error->build()];
	}

}
