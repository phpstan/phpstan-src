<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class DummyRule implements Rule
{

	public function getNodeType(): string
	{
		return 'PhpParser\Node\Expr\FuncCall';
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [];
	}

}
