<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Error;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<ClassMethod>
 */
#[RegisteredRule(level: 0)]
final class ReportPropertiesThatShouldBePromotedRule implements Rule
{

	public function __construct(
		#[AutowiredParameter]
		private bool $reportPropertiesThatShouldBePromoted,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassMethod::class;
	}

	#[Override]
	public function processNode(Node $node, Scope $scope): array
	{
		if (
			! $this->reportPropertiesThatShouldBePromoted
				|| $node->name->toLowerString() !== '__construct'
				|| $node->params === null
		) {
			return [];
		}
		$errors = [];
		foreach ($node->params as $param) {
			if (
				$param->isPromoted()
					|| $param->var instanceof Error
					|| $param->var->name instanceof Expr
					|| ! $this->assignsUnmodifiedVariableToProperty($param->var->name, $node)
			) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf('Property [%s] should be promoted.', $param->var->name))
				->identifier('property.shouldBePromoted')
				->line($param->var->getStartLine())
				->build();
		}
		return $errors;
	}

	private function assignsUnmodifiedVariableToProperty(string $variable, ClassMethod $node): bool
	{
		foreach ($node->getStmts() as $stmt) {
			if (! $stmt instanceof Expression || ! $stmt->expr instanceof Assign) {
				continue;
			}
			$var = $stmt->expr->var;
			$expr = $stmt->expr->expr;
			if (! $var instanceof Variable && ! $var instanceof PropertyFetch) {
				continue;
			}
			if ($var instanceof Variable) {
				// The variable has been modified, so can't promote it.
				if ($var->name === $variable) {
					return false;
				}
				continue;
			}
			if (
				! $var->var instanceof Variable
					|| $var->var->name !== 'this'
					|| ! $var->name instanceof Identifier
					|| $var->name->toString() !== $variable
			) {
				continue;
			}
			if (! $expr instanceof Variable) {
				continue;
			}
			// The variable is being assigned to a property
			// of the same name, safe to promote it.
			if ($expr->name === $variable) {
				return true;
			}
		}
		return false;
	}

}
