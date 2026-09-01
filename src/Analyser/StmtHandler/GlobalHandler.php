<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Global_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\VarAnnotationProcessor;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use function array_merge;
use function is_string;

/**
 * @implements StmtHandler<Global_>
 */
#[AutowiredService]
final class GlobalHandler implements StmtHandler
{

	public function __construct(
		private VarAnnotationProcessor $varAnnotationProcessor,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Global_;
	}

	private function getGlobalVariableType(string $variableName): Type
	{
		if ($variableName === 'argc') {
			return StaticTypeFactory::argc();
		}
		if ($variableName === 'argv') {
			return StaticTypeFactory::argv();
		}

		return new MixedType();
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
				'global',
				'global variable',
				true,
			),
		];
		$vars = [];
		foreach ($stmt->vars as $var) {
			if (!$var instanceof Variable) {
				throw new ShouldNotHappenException();
			}
			$scope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($scope, $var);
			$varResult = $nodeScopeResolver->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$impurePoints = array_merge($impurePoints, $varResult->getImpurePoints());
			$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $var);

			if (!is_string($var->name)) {
				continue;
			}

			$varType = $this->getGlobalVariableType($var->name);
			$nodeScopeResolver->markVariableUntracked($var->name);
			$scope = $scope->assignVariable($var->name, $varType, $varType, TrinaryLogic::createYes());
			$vars[] = $var->name;
		}
		$scope = $this->varAnnotationProcessor->processVarAnnotation($scope, $vars, $stmt);

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: $impurePoints);
	}

}
