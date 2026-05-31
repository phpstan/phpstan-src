<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<UsedAsStringNode>
 */
class UsedAsStringRule implements Rule
{

	private Standard $printer;

	public function __construct()
	{
		$this->printer = new Standard();
	}

	public function getNodeType(): string
	{
		return UsedAsStringNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$expr = $node->getExpr();

		return [
			RuleErrorBuilder::message(sprintf(
				'Used as string: %s (%s)',
				$this->printer->prettyPrintExpr($expr),
				$scope->getType($expr)->describe(VerbosityLevel::precise()),
			))->identifier('tests.usedAsString')->build(),
		];
	}

}
