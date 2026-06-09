<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Pipe;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\ReversePipeTransformerVisitor;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<Pipe>
 */
#[AutowiredService]
final class PipeHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Pipe;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->right instanceof FuncCall && $expr->right->isFirstClassCallable()) {
			return $scope->getType(new FuncCall($expr->right->name, [
				new Arg($expr->left),
			]));
		} elseif ($expr->right instanceof MethodCall && $expr->right->isFirstClassCallable()) {
			return $scope->getType(new MethodCall($expr->right->var, $expr->right->name, [
				new Arg($expr->left),
			]));
		} elseif ($expr->right instanceof StaticCall && $expr->right->isFirstClassCallable()) {
			return $scope->getType(new StaticCall($expr->right->class, $expr->right->name, [
				new Arg($expr->left),
			]));
		}

		return $scope->getType(new FuncCall($expr->right, [
			new Arg($expr->left),
		]));
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$rightAttributes = array_merge($expr->right->getAttributes(), ['virtualPipeOperatorCall' => true]);
		unset($rightAttributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		$argAttributes = $expr->getAttribute(ReversePipeTransformerVisitor::ARG_ATTRIBUTES_NAME, []);

		if ($expr->right instanceof FuncCall && $expr->right->isFirstClassCallable()) {
			$callExpr = new FuncCall($expr->right->name, [
				new Arg($expr->left, attributes: $argAttributes),
			], $rightAttributes);
		} elseif ($expr->right instanceof MethodCall && $expr->right->isFirstClassCallable()) {
			$callExpr = new MethodCall($expr->right->var, $expr->right->name, [
				new Arg($expr->left, attributes: $argAttributes),
			], $rightAttributes);
		} elseif ($expr->right instanceof StaticCall && $expr->right->isFirstClassCallable()) {
			$callExpr = new StaticCall($expr->right->class, $expr->right->name, [
				new Arg($expr->left, attributes: $argAttributes),
			], $rightAttributes);
		} else {
			$callExpr = new FuncCall($expr->right, [
				new Arg($expr->left, attributes: $argAttributes),
			], $rightAttributes);
		}

		$callResult = $nodeScopeResolver->processExprNode($stmt, $callExpr, $scope, $storage, $nodeCallback, $context);

		return new ExpressionResult(
			$callResult->getScope(),
			hasYield: $callResult->hasYield(),
			isAlwaysTerminating: $callResult->isAlwaysTerminating(),
			throwPoints: $callResult->getThrowPoints(),
			impurePoints: $callResult->getImpurePoints(),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
