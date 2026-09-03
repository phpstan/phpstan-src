<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\DeprecatedAttributeResolver;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\PhpDocsResolver;
use PHPStan\Analyser\PropertyHooksProcessor;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\MixedType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\TypeUtils;
use function array_merge;
use function is_string;

/**
 * @implements StmtHandler<ClassMethod>
 */
#[AutowiredService]
final class ClassMethodHandler implements StmtHandler
{

	public function __construct(
		private DeprecatedAttributeResolver $deprecatedAttributeResolver,
		private PhpDocsResolver $phpDocsResolver,
		private PropertyHooksProcessor $propertyHooksProcessor,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof ClassMethod;
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
		$nodeScopeResolver->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);
		[$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, $isReadOnly, $phpDocComment, $asserts, $selfOutType, $phpDocParameterOutTypes, , , , $pureUnlessCallableIsImpureParameters] = $this->phpDocsResolver->getPhpDocs($scope, $stmt);

		foreach ($stmt->params as $param) {
			$nodeScopeResolver->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
		}

		if ($stmt->returnType !== null) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt->returnType, $scope, $storage);
		}

		if (!$isDeprecated) {
			[$isDeprecated, $deprecatedDescription] = $this->deprecatedAttributeResolver->getDeprecatedAttribute($scope, $stmt);
		}

		$isFromTrait = $stmt->getAttribute('originalTraitMethodName') === '__construct';
		$isConstructor = $isFromTrait || $stmt->name->toLowerString() === '__construct';

		$methodScope = $scope->enterClassMethod(
			$stmt,
			$templateTypeMap,
			$phpDocParameterTypes,
			$phpDocReturnType,
			$phpDocThrowType,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$isFinal,
			$isPure,
			$acceptsNamedArguments,
			$asserts,
			$selfOutType,
			$phpDocComment,
			$phpDocParameterOutTypes,
			$phpDocImmediatelyInvokedCallableParameters,
			$phpDocClosureThisTypeParameters,
			$isConstructor,
			null,
			$pureUnlessCallableIsImpureParameters,
		);

		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$classReflection = $scope->getClassReflection();

		if ($isConstructor) {
			foreach ($stmt->params as $param) {
				if ($param->flags === 0 && $param->hooks === []) {
					continue;
				}

				if (!$param->var instanceof Variable || !is_string($param->var->name) || $param->var->name === '') {
					throw new ShouldNotHappenException();
				}
				$phpDoc = null;
				if ($param->getDocComment() !== null) {
					$phpDoc = $param->getDocComment()->getText();
				}
				$nodeScopeResolver->callNodeCallback($nodeCallback, new ClassPropertyNode(
					$param->var->name,
					$param->flags,
					$param->type !== null ? ParserNodeTypeToPHPStanType::resolve($param->type, $classReflection) : null,
					null,
					$phpDoc,
					$phpDocParameterTypes[$param->var->name] ?? null,
					true,
					$isFromTrait,
					$param,
					$isReadOnly,
					$scope->isInTrait(),
					$classReflection->isReadOnly(),
					false,
					$classReflection,
				), $methodScope, $storage);
				$this->propertyHooksProcessor->processPropertyHooks(
					$nodeScopeResolver,
					$stmt,
					$param->type,
					$phpDocParameterTypes[$param->var->name] ?? null,
					$param->var->name,
					$param->hooks,
					$scope,
					$storage,
					$nodeCallback,
				);
				$methodScope = $methodScope->assignExpression(new PropertyInitializationExpr($param->var->name), new MixedType(), new MixedType());
			}
		}

		if ($stmt->getAttribute('virtual', false) === false) {
			$methodReflection = $methodScope->getFunction();
			if (!$methodReflection instanceof PhpMethodFromParserNodeReflection) {
				throw new ShouldNotHappenException();
			}
			$nodeScopeResolver->callNodeCallback($nodeCallback, new InClassMethodNode($classReflection, $methodReflection, $stmt), $methodScope, $storage);
		}

		if ($stmt->stmts !== null) {
			$gatheredReturnStatements = [];
			$gatheredYieldStatements = [];
			$executionEnds = [];
			$methodImpurePoints = [];
				$nodeScopeResolver->pushNodeGatherer(static function (Node $node, Scope $scope) use ($methodScope, &$gatheredReturnStatements, &$gatheredYieldStatements, &$executionEnds, &$methodImpurePoints): void {
					if ($scope->getFunction() !== $methodScope->getFunction()) {
						return;
					}
					if ($scope->isInAnonymousFunction()) {
						return;
					}
					if ($node instanceof PropertyAssignNode) {
						if (
						$node->getPropertyFetch() instanceof Expr\PropertyFetch
						&& $scope->getFunction() instanceof PhpMethodFromParserNodeReflection
						&& $scope->getFunction()->getDeclaringClass()->hasConstructor()
						&& $scope->getFunction()->getDeclaringClass()->getConstructor()->getName() === $scope->getFunction()->getName()
						&& TypeUtils::findThisType($scope->getType($node->getPropertyFetch()->var)) !== null
						) {
							return;
						}
						$methodImpurePoints[] = new ImpurePoint(
							$scope,
							$node,
							'propertyAssign',
							'property assignment',
							true,
						);
						return;
					}
					if ($node instanceof ExecutionEndNode) {
						$executionEnds[] = $node;
						return;
					}
					if ($node instanceof Expr\Yield_ || $node instanceof Expr\YieldFrom) {
						$gatheredYieldStatements[] = $node;
					}
					if (!$node instanceof Return_) {
						return;
					}

					$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
				});
			try {
				$statementResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $methodScope, $storage, $nodeCallback, StatementContext::createTopLevel())->toPublic();
			} finally {
				$nodeScopeResolver->popNodeGatherer();
			}

			$methodReflection = $methodScope->getFunction();
			if (!$methodReflection instanceof PhpMethodFromParserNodeReflection) {
				throw new ShouldNotHappenException();
			}

			$nodeScopeResolver->callNodeCallback($nodeCallback, new MethodReturnStatementsNode(
				$stmt,
				$gatheredReturnStatements,
				$gatheredYieldStatements,
				$statementResult,
				$executionEnds,
				array_merge($statementResult->getImpurePoints(), $methodImpurePoints),
				$classReflection,
				$methodReflection,
			), $methodScope, $storage);

			if ($isConstructor) {
				$finalScope = null;

				foreach ($executionEnds as $executionEnd) {
					if ($executionEnd->getStatementResult()->isAlwaysTerminating()) {
						continue;
					}

					$endScope = $executionEnd->getStatementResult()->getScope()->toWalkScope();
					if ($finalScope === null) {
						$finalScope = $endScope;
						continue;
					}

					$finalScope = $finalScope->mergeWith($endScope);
				}

				foreach ($gatheredReturnStatements as $statement) {
					if ($finalScope === null) {
						$finalScope = $statement->getScope()->toWalkScope();
						continue;
					}

					$finalScope = $finalScope->mergeWith($statement->getScope()->toWalkScope());
				}

				if ($finalScope !== null) {
					$scope = $finalScope->rememberConstructorScope();
				}

			}
		}

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
	}

}
