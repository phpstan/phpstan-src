<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Static_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\Generics\StaticVariableInference;
use PHPStan\Analyser\Generics\VarTagUsagesInference;
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
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use function array_merge;
use function is_string;

/**
 * @implements StmtHandler<Static_>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/StaticVariableHandler.cpp')]
final class StaticVariableHandler implements StmtHandler
{

	public function __construct(
		private VarAnnotationProcessor $varAnnotationProcessor,
		private StaticVariableInference $staticVariableInference,
		private VarTagUsagesInference $varTagUsagesInference,
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

		// the walk that finds what the variable takes without its @var tag
		// (see VarTagUsagesInference) starts from the default and leaves the
		// tag out
		$varTagSuppressed = $this->varTagUsagesInference->isSuppressed($scope, $stmt);
		$vars = [];
		$variableFlows = [];
		foreach ($stmt->vars as $var) {
			if (!is_string($var->var->name)) {
				throw new ShouldNotHappenException();
			}

			$defaultExprResult = null;
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

			// the type the previous calls may have left - see StaticVariableInference
			$types = $this->staticVariableInference->getResolvedTypes($scope, $var->var);
			if ($types === null && ($varTagSuppressed || $this->staticVariableInference->isInferred($scope, $var->var))) {
				$types = $defaultExprResult !== null
					? [$defaultExprResult->getType(), $defaultExprResult->getNativeType()]
					: [new NullType(), new NullType()];
			}
			[$type, $nativeType] = $types ?? [new MixedType(), new MixedType()];
			$scope = $scope->assignVariable($var->var->name, $type, $nativeType, TrinaryLogic::createYes());
			$vars[] = $var->var->name;
		}

		if (!$varTagSuppressed) {
			$scope = $this->varAnnotationProcessor->processVarAnnotation($scope, $vars, $stmt);
		}

		// how the types of a run of `static` variables depend on each other -
		// see StaticVariableInference::getRuns()
		foreach ($this->staticVariableInference->getResolvedConditionalExpressions($scope, $stmt) as $exprString => $holders) {
			$scope = $scope->addConditionalExpressions($exprString, $holders);
		}

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: $impurePoints, variableFlow: VariableFlow::sequence(...$variableFlows));
	}

}
