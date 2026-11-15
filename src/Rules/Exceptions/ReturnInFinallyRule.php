<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function is_array;

/**
 * @implements Rule<Stmt\TryCatch>
 */
#[RegisteredRule(level: 0)]
final class ReturnInFinallyRule implements Rule
{

	public function __construct(
		#[AutowiredParameter(ref: '%featureToggles.checkWholePhpVersionRange%')]
		private bool $checkWholePhpVersionRange,
	)
	{
	}

	public function getNodeType(): string
	{
		return Stmt\TryCatch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->finally === null) {
			return [];
		}

		$deprecatesReturnInFinally = $scope->getPhpVersion()->deprecatesReturnInFinally();
		if ($this->checkWholePhpVersionRange ? $deprecatesReturnInFinally->no() : !$deprecatesReturnInFinally->yes()) {
			return [];
		}

		$errors = [];
		foreach ($this->findReturns($node->finally->stmts) as $return) {
			$errors[] = RuleErrorBuilder::message('Returning from a finally block is deprecated in PHP 8.6.')
				->line($return->getStartLine())
				->identifier('finally.deprecatedReturn')
				->build();
		}

		return $errors;
	}

	/**
	 * Like PHP, looks for return statements lexically, without descending into
	 * functions, classes, and expressions (which is where closures live).
	 * A nested try's own finally block is left out because this rule reports it separately.
	 *
	 * @param Node[] $nodes
	 * @return list<Stmt\Return_>
	 */
	private function findReturns(array $nodes): array
	{
		$returns = [];
		foreach ($nodes as $node) {
			if (!$node instanceof Stmt || $node instanceof Stmt\Function_ || $node instanceof Stmt\ClassLike) {
				continue;
			}

			if ($node instanceof Stmt\Return_) {
				$returns[] = $node;
				continue;
			}

			$subNodeNames = $node instanceof Stmt\TryCatch ? ['stmts', 'catches'] : $node->getSubNodeNames();
			foreach ($subNodeNames as $subNodeName) {
				$subNode = $node->$subNodeName;
				foreach ($this->findReturns(is_array($subNode) ? $subNode : [$subNode]) as $return) {
					$returns[] = $return;
				}
			}
		}

		return $returns;
	}

}
