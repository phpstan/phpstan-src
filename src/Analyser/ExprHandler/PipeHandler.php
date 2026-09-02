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
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Parser\ReversePipeTransformerVisitor;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<Pipe>
 */
#[AutowiredService]
final class PipeHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Pipe;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$rightAttributes = array_merge($expr->right->getAttributes(), ['virtualPipeOperatorCall' => true]);
		unset($rightAttributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		$argAttributes = $expr->getAttribute(ReversePipeTransformerVisitor::ARG_ATTRIBUTES_NAME, []);

		$firstClassCallableNode = null;
		if ($expr->right instanceof FuncCall && $expr->right->isFirstClassCallable()) {
			$callExpr = new FuncCall($expr->right->name, [
				new Arg($expr->left, attributes: $argAttributes),
			], $rightAttributes);
			$firstClassCallableNode = new FunctionCallableNode($expr->right->name, $expr->right);
		} elseif ($expr->right instanceof MethodCall && $expr->right->isFirstClassCallable()) {
			$callExpr = new MethodCall($expr->right->var, $expr->right->name, [
				new Arg($expr->left, attributes: $argAttributes),
			], $rightAttributes);
			$firstClassCallableNode = new MethodCallableNode($expr->right->var, $expr->right->name, $expr->right);
		} elseif ($expr->right instanceof StaticCall && $expr->right->isFirstClassCallable()) {
			$callExpr = new StaticCall($expr->right->class, $expr->right->name, [
				new Arg($expr->left, attributes: $argAttributes),
			], $rightAttributes);
			$firstClassCallableNode = new StaticMethodCallableNode($expr->right->class, $expr->right->name, $expr->right);
		} else {
			$callExpr = new FuncCall($expr->right, [
				new Arg($expr->left, attributes: $argAttributes),
			], $rightAttributes);
		}

		if ($firstClassCallableNode !== null) {
			// store a result for $expr->right so node callbacks asking about its
			// type can be resumed. Its closure type lives on the matching
			// *CallableNode, processed here (storage is available, so the result -
			// not the storage - is captured) and read back in the typeCallback.
			$callableNodeResult = $nodeScopeResolver->processExprOnDemand($firstClassCallableNode, $scope, $storage);
			$nodeScopeResolver->storeExpressionResult($storage, $expr->right, $this->expressionResultFactory->create(
				$scope,
				beforeScope: $scope,
				expr: $expr->right,
				hasYield: false,
				isAlwaysTerminating: false,
				throwPoints: [],
				impurePoints: [],
				typeCallback: static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ? $callableNodeResult->getNativeType() : $callableNodeResult->getType()),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			));
		}

		$callResult = $nodeScopeResolver->processExprNode($stmt, $callExpr, $scope, $storage, $nodeCallback, $context);

		return $this->expressionResultFactory->create(
			$callResult->getScope(),
			beforeScope: $scope,
			expr: $expr,
			hasYield: $callResult->hasYield(),
			isAlwaysTerminating: $callResult->isAlwaysTerminating(),
			throwPoints: $callResult->getThrowPoints(),
			impurePoints: $callResult->getImpurePoints(),
			// the pipe evaluates to its rewritten call - read that child's result
			typeCallback: static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ? $callResult->getNativeType() : $callResult->getType()),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

}
