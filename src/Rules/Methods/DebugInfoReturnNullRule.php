<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
#[RegisteredRule(level: 0)]
final class DebugInfoReturnNullRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->deprecatesDebugInfoReturningNull()) {
			return [];
		}

		if (strtolower($node->getMethodName()) !== '__debuginfo' || $node->isGenerator()) {
			return [];
		}

		$method = $node->getMethodReflection();
		if ($method->isAbstract()->yes()) {
			return [];
		}

		$message = sprintf(
			'Returning null from %s::%s() is deprecated in PHP 8.5.',
			$node->getClassReflection()->getDisplayName(),
			$method->getName(),
		);

		$errors = [];
		foreach ($node->getReturnStatements() as $returnStatement) {
			$returnNode = $returnStatement->getReturnNode();
			if (
				$returnNode->expr !== null
				&& !$returnStatement->getScope()->getType($returnNode->expr)->isNull()->yes()
			) {
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message($message)
				->line($returnNode->getStartLine())
				->tip('Return an empty array instead.')
				->identifier('method.debugInfoNull');

			// replacing other expressions would drop their side effects
			if (
				$returnNode->expr === null
				|| $returnNode->expr instanceof Node\Expr\ConstFetch
				|| $returnNode->expr instanceof Node\Expr\Variable
			) {
				$errorBuilder->fixNode($returnNode, static function (Return_ $returnNode): Return_ {
					$returnNode->expr = new Node\Expr\Array_();

					return $returnNode;
				});
			}

			$errors[] = $errorBuilder->build();
		}

		if (!$node->getStatementResult()->isAlwaysTerminating()) {
			$errors[] = RuleErrorBuilder::message($message)
				->tip('The method returns null implicitly. Return an empty array instead.')
				->identifier('method.debugInfoNull')
				->build();
		}

		return $errors;
	}

}
