<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Node\PropertyHookStatementNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Parser\LineAttributesVisitor;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use function array_merge;

#[AutowiredService]
final class PropertyHooksProcessor
{

	public function __construct(
		private PhpDocsResolver $phpDocsResolver,
	)
	{
	}

	/**
	 * @param Node\PropertyHook[] $hooks
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processPropertyHooks(
		NodeScopeResolver $nodeScopeResolver,
		Node\Stmt $stmt,
		Identifier|Name|ComplexType|null $nativeTypeNode,
		?Type $phpDocType,
		string $propertyName,
		array $hooks,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
	): void
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$classReflection = $scope->getClassReflection();

		foreach ($hooks as $hook) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $hook, $scope, $storage);
			$nodeScopeResolver->processAttributeGroups($stmt, $hook->attrGroups, $scope, $storage, $nodeCallback);

			[, $phpDocParameterTypes,,,, $phpDocThrowType,,,,,,,, $phpDocComment,,,,,, $resolvedPhpDoc] = $this->phpDocsResolver->getPhpDocs($scope, $hook);

			foreach ($hook->params as $param) {
				$nodeScopeResolver->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
			}

			[$isDeprecated, $deprecatedDescription] = $nodeScopeResolver->getDeprecatedAttribute($scope, $hook);

			$hookScope = $scope->enterPropertyHook(
				$hook,
				$propertyName,
				$nativeTypeNode,
				$phpDocType,
				$phpDocParameterTypes,
				$phpDocThrowType,
				$deprecatedDescription,
				$isDeprecated,
				$phpDocComment,
				$resolvedPhpDoc,
			);
			$hookReflection = $hookScope->getFunction();
			if (!$hookReflection instanceof PhpMethodFromParserNodeReflection) {
				throw new ShouldNotHappenException();
			}

			if (!$classReflection->hasNativeProperty($propertyName)) {
				throw new ShouldNotHappenException();
			}

			$propertyReflection = $classReflection->getNativeProperty($propertyName);

			$nodeScopeResolver->callNodeCallback($nodeCallback, new InPropertyHookNode(
				$classReflection,
				$hookReflection,
				$propertyReflection,
				$hook,
			), $hookScope, $storage);

			$stmts = $hook->getStmts();
			if ($stmts === null) {
				return;
			}

			if ($hook->body instanceof Expr) {
				// enrich attributes of nodes in short hook body statements
				$traverser = new NodeTraverser(
					new LineAttributesVisitor($hook->body->getStartLine(), $hook->body->getEndLine()),
				);
				$traverser->traverse($stmts);
			}

			$gatheredReturnStatements = [];
			$executionEnds = [];
			$methodImpurePoints = [];
			$statementResult = $nodeScopeResolver->processStmtNodesInternal(new PropertyHookStatementNode($hook), $stmts, $hookScope, $storage, new GatheringNodeCallback(static function (Node $node, Scope $scope) use ($hookScope, &$gatheredReturnStatements, &$executionEnds, &$hookImpurePoints): void {
				if ($scope->getFunction() !== $hookScope->getFunction()) {
					return;
				}
				if ($scope->isInAnonymousFunction()) {
					return;
				}
				if ($node instanceof PropertyAssignNode) {
					$hookImpurePoints[] = new ImpurePoint(
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
				if (!$node instanceof Return_) {
					return;
				}

				$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
			}, $nodeCallback), StatementContext::createTopLevel())->toPublic();

			$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyHookReturnStatementsNode(
				$hook,
				$gatheredReturnStatements,
				$statementResult,
				$executionEnds,
				array_merge($statementResult->getImpurePoints(), $methodImpurePoints),
				$classReflection,
				$hookReflection,
				$propertyReflection,
			), $hookScope, $storage);
		}
	}

}
