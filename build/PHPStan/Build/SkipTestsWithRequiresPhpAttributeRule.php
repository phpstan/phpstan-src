<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;
use function array_values;
use function count;
use function get_class;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
final class SkipTestsWithRequiresPhpAttributeRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodReflection = $node->getMethodReflection();
		if (!$methodReflection->getDeclaringClass()->is(TestCase::class)) {
			return [];
		}

		$originalNode = $node->getOriginalNode();
		if ($originalNode->stmts === null) {
			return [];
		}

		if (count($originalNode->stmts) === 0) {
			return [];
		}

		$firstStmt = $originalNode->stmts[0];
		if (!$firstStmt instanceof Node\Stmt\If_) {
			return [];
		}

		if (!$firstStmt->cond instanceof Node\Expr\BinaryOp) {
			return [];
		}

		switch (get_class($firstStmt->cond)) {
			case Node\Expr\BinaryOp\SmallerOrEqual::class:
				$inverseBinaryOpSigil = '>';
				break;
			case Node\Expr\BinaryOp\Smaller::class:
				$inverseBinaryOpSigil = '>=';
				break;
			case Node\Expr\BinaryOp\GreaterOrEqual::class:
				$inverseBinaryOpSigil = '<';
				break;
			case Node\Expr\BinaryOp\Greater::class:
				$inverseBinaryOpSigil = '<=';
				break;
			case Node\Expr\BinaryOp\Identical::class:
				$inverseBinaryOpSigil = '!==';
				break;
			case Node\Expr\BinaryOp\NotIdentical::class:
				$inverseBinaryOpSigil = '===';
				break;
			default:
				throw new ShouldNotHappenException('No inverse comparison specified for ' . get_class($firstStmt->cond));
		}

		if (!$firstStmt->cond->left instanceof Node\Expr\ConstFetch || $firstStmt->cond->left->name->toString() !== 'PHP_VERSION_ID') {
			return [];
		}

		if (!$firstStmt->cond->right instanceof Node\Scalar\Int_) {
			return [];
		}

		if (count($firstStmt->stmts) !== 1) {
			return [];
		}

		$ifStmt = $firstStmt->stmts[0];
		if (!$ifStmt instanceof Node\Stmt\Expression) {
			return [];
		}

		if (!$ifStmt->expr instanceof Node\Expr\StaticCall && !$ifStmt->expr instanceof Node\Expr\MethodCall) {
			return [];
		}

		if (!$ifStmt->expr->name instanceof Node\Identifier || $ifStmt->expr->name->toLowerString() !== 'marktestskipped') {
			return [];
		}

		$phpVersion = new PhpVersion($firstStmt->cond->right->value);

		return [
			RuleErrorBuilder::message('Skip tests with #[RequiresPhp] attribute instead.')
				->identifier('phpstan.skipTestsRequiresPhp')
				->line($firstStmt->getStartLine())
				->fixNode($originalNode, static function (Node\Stmt\ClassMethod $node) use ($phpVersion, $inverseBinaryOpSigil) {
					$stmts = $node->stmts;
					if ($stmts === null) {
						return $node;
					}

					unset($stmts[0]);
					$node->stmts = array_values($stmts);
					$node->attrGroups[] = new Node\AttributeGroup([
						new Attribute(new Node\Name\FullyQualified('PHPUnit\\Framework\\Attributes\\RequiresPhp'), [
							new Node\Arg(new Node\Scalar\String_(sprintf('%s %s', $inverseBinaryOpSigil, $phpVersion->getVersionString()))),
						]),
					]);

					return $node;
				})
				->build(),
		];
	}


}
