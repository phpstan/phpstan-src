<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
class DefineParametersRule implements \PHPStan\Rules\Rule
{

	private PhpVersion $phpVersion;

	public function __construct(PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof \PhpParser\Node\Name)) {
			return [];
		}
		if ($this->phpVersion->getVersionId() < 80000) {
			return [];
		}
		$name = strtolower((string) $node->name);
		if ($name !== 'define') {
			return [];
		}
		$args = $node->getArgs();
		$argsCount = count($args);
		// Expects 2, 1 arg is caught by CallToFunctionParametersRule
		if ($argsCount < 3) {
			return [];
		}
		return [
			'Argument #3 ($case_insensitive) is ignored since declaration of case-insensitive constants is no longer supported.',
		];
	}

}
