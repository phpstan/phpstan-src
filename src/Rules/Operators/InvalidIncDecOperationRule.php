<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\VerbosityLevel;
use function get_class;
use function sprintf;

/**
 * @implements Rule<Node\Expr>
 */
class InvalidIncDecOperationRule implements Rule
{

	public function __construct(private bool $checkThisOnly)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Expr\PreInc
			&& !$node instanceof Node\Expr\PostInc
			&& !$node instanceof Node\Expr\PreDec
			&& !$node instanceof Node\Expr\PostDec
		) {
			return [];
		}

		switch (get_class($node)) {
			case Node\Expr\PreInc::class:
				$nodeType = 'preInc';
				break;
			case Node\Expr\PostInc::class:
				$nodeType = 'postInc';
				break;
			case Node\Expr\PreDec::class:
				$nodeType = 'preDec';
				break;
			case Node\Expr\PostDec::class:
				$nodeType = 'postDec';
				break;
			default:
				throw new ShouldNotHappenException();
		}

		$operatorString = $node instanceof Node\Expr\PreInc || $node instanceof Node\Expr\PostInc ? '++' : '--';

		if (
			!$node->var instanceof Node\Expr\Variable
			&& !$node->var instanceof Node\Expr\ArrayDimFetch
			&& !$node->var instanceof Node\Expr\PropertyFetch
			&& !$node->var instanceof Node\Expr\StaticPropertyFetch
		) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot use %s on a non-variable.',
					$operatorString,
				))
					->line($node->var->getStartLine())
					->identifier(sprintf('%s.expr', $nodeType))
					->build(),
			];
		}

		if (!$this->checkThisOnly) {
			$varType = $scope->getType($node->var);
			if (!$varType->toString() instanceof ErrorType) {
				return [];
			}
			if (!$varType->toNumber() instanceof ErrorType) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot use %s on %s.',
					$operatorString,
					$varType->describe(VerbosityLevel::value()),
				))
					->line($node->var->getStartLine())
					->identifier(sprintf('%s.type', $nodeType))
					->build(),
			];
		}

		return [];
	}

}
