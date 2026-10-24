<?php declare(strict_types = 1);

namespace ResultCacheE2ERuleEdit;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
final class R implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Name) {
			return [];
		}

		$name = $node->name->toString();
		if ($name === 'foo') {
			return [RuleErrorBuilder::message('R: foo v1')->identifier('r.foo')->build()];
		}
		if ($name === 'elsewhere') {
			return [RuleErrorBuilder::message('R: reported in Other.php v1')->identifier('r.elsewhere')->file(realpath(__DIR__ . '/../src/Other.php'))->line(3)->build()];
		}

		return [];
	}

}
