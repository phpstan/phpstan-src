<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\StandaloneThrowExprVisitor;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Throw_>
 */
class ThrowExpressionRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Throw_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->phpVersion->supportsThrowExpression()) {
			return [];
		}

		if ($node->getAttribute(StandaloneThrowExprVisitor::ATTRIBUTE_NAME) === true) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Throw expression is supported only on PHP 8.0 and later.')->nonIgnorable()
				->identifier('throw.notSupported')
				->build(),
		];
	}

}
