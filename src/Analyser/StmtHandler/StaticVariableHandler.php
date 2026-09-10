<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Static_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\VarAnnotationProcessor;
use PHPStan\Analyser\VariableFlow;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use function array_merge;
use function is_string;

/**
 * @implements StmtHandler<Static_>
 */
#[AutowiredService]
final class StaticVariableHandler implements StmtHandler
{

	public function __construct(
		private VarAnnotationProcessor $varAnnotationProcessor,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Static_;
	}

	public function processStmt(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$impurePoints = [
			new ImpurePoint(
				$scope,
				$stmt,
				'static',
				'static variable',
				true,
			),
		];

		$vars = [];
		$variableFlows = [];
		foreach ($stmt->vars as $var) {
			if (!is_string($var->var->name)) {
				throw new ShouldNotHappenException();
			}

			if ($var->default !== null) {
				$defaultExprResult = $nodeScopeResolver->processExprNode($stmt, $var->default, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
				$variableFlows[] = $defaultExprResult->getVariableFlow();
				$impurePoints = array_merge($impurePoints, $defaultExprResult->getImpurePoints());
			}

			$scope = $scope->enterExpressionAssign($var->var);
			$variableFlows[] = VariableFlow::escape($var->var->name);
			$varResult = $nodeScopeResolver->processExprNode($stmt, $var->var, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			$impurePoints = array_merge($impurePoints, $varResult->getImpurePoints());
			$scope = $scope->exitExpressionAssign($var->var);

			$scope = $scope->assignVariable($var->var->name, new MixedType(), new MixedType(), TrinaryLogic::createYes());
			$vars[] = $var->var->name;
		}

		$scope = $this->varAnnotationProcessor->processVarAnnotation($scope, $vars, $stmt);

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: $impurePoints, variableFlow: VariableFlow::sequence(...$variableFlows));
	}

}
