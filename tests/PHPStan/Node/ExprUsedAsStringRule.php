<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<ExprUsedAsStringNode>
 */
class ExprUsedAsStringRule implements Rule
{

	private Standard $printer;

	public function __construct()
	{
		$this->printer = new Standard();
	}

	public function getNodeType(): string
	{
		return ExprUsedAsStringNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$inner = $node->getNode();

		if ($inner instanceof Expr) {
			$printed = $this->printer->prettyPrintExpr($inner);
			$type = $scope->getType($inner)->describe(VerbosityLevel::precise());
		} elseif ($inner instanceof InlineHTML) {
			$printed = $this->printer->prettyPrintExpr(new String_($inner->value, $inner->getAttributes()));
			$type = (new ConstantStringType($inner->value))->describe(VerbosityLevel::precise());
		} else {
			throw new ShouldNotHappenException();
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Used as string: %s (%s)',
				$printed,
				$type,
			))->identifier('tests.exprUsedAsString')->build(),
		];
	}

}
