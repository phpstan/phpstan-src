<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class DefineParametersRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}
		if ($this->phpVersion->supportsCaseInsensitiveConstantNames()) {
			return [];
		}
		$name = strtolower((string) $node->name);
		if ($name !== 'define') {
			return [];
		}
		$args = $node->getArgs();
		$argsCount = count($args);
		// Expects 2 or 3, 1 arg is caught by CallToFunctionParametersRule
		if ($argsCount < 3) {
			return [];
		}
		return [
			RuleErrorBuilder::message(
				'Argument #3 ($case_insensitive) is ignored since declaration of case-insensitive constants is no longer supported.',
			)
				->line($node->getLine())
				->identifier('argument.unused')
				->build(),
		];
	}

}
