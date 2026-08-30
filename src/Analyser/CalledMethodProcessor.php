<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileHelper;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\NeverType;
use function array_key_exists;
use function count;
use function is_array;
use function sprintf;

#[AutowiredService]
final class CalledMethodProcessor
{

	/** @var array<string, true> */
	private array $calledMethodStack = [];

	/** @var array<string, MutatingScope|null> */
	private array $calledMethodResults = [];

	public function __construct(
		private FileHelper $fileHelper,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private Parser $parser,
		private ScopeFactory $scopeFactory,
	)
	{
	}

	public function processCalledMethod(NodeScopeResolver $nodeScopeResolver, MethodReflection $methodReflection): ?MutatingScope
	{
		$declaringClass = $methodReflection->getDeclaringClass();
		if ($declaringClass->isAnonymous()) {
			return null;
		}
		if ($declaringClass->getFileName() === null) {
			return null;
		}

		$stackName = sprintf('%s::%s', $declaringClass->getName(), $methodReflection->getName());
		if (array_key_exists($stackName, $this->calledMethodResults)) {
			return $this->calledMethodResults[$stackName];
		}

		if (array_key_exists($stackName, $this->calledMethodStack)) {
			return null;
		}

		if (count($this->calledMethodStack) > 0) {
			return null;
		}

		$this->calledMethodStack[$stackName] = true;

		$fileName = $this->fileHelper->normalizePath($declaringClass->getFileName());
		if (!$nodeScopeResolver->isAnalysedFile($fileName)) {
			unset($this->calledMethodStack[$stackName]);
			return null;
		}
		$parserNodes = $this->parser->parseFile($fileName);

		$returnStatement = null;
		$this->processNodesForCalledMethod($nodeScopeResolver, $parserNodes, new ExpressionResultStorage(), $fileName, $methodReflection, static function (Node $node, Scope $scope) use ($methodReflection, &$returnStatement): void {
			if (!$node instanceof MethodReturnStatementsNode) {
				return;
			}

			if ($node->getClassReflection()->getName() !== $methodReflection->getDeclaringClass()->getName()) {
				return;
			}

			if ($returnStatement !== null) {
				return;
			}

			$returnStatement = $node;
		});

		$calledMethodEndScope = null;
		if ($returnStatement !== null) {
			foreach ($returnStatement->getExecutionEnds() as $executionEnd) {
				$statementResult = $executionEnd->getStatementResult();
				$endExprResult = $executionEnd->getExprResult();
				if ($endExprResult !== null) {
					$walkScope = $statementResult->getScope()->toWalkScope();
					$exprType = $endExprResult->getTypeOnScope($walkScope, $walkScope->nativeTypesPromoted);
					if ($exprType instanceof NeverType && $exprType->isExplicit()) {
						continue;
					}
				}
				if ($calledMethodEndScope === null) {
					$calledMethodEndScope = $statementResult->getScope();
					continue;
				}

				$calledMethodEndScope = $calledMethodEndScope->mergeWith($statementResult->getScope());
			}
			foreach ($returnStatement->getReturnStatements() as $statement) {
				if ($calledMethodEndScope === null) {
					$calledMethodEndScope = $statement->getScope();
					continue;
				}

				$calledMethodEndScope = $calledMethodEndScope->mergeWith($statement->getScope());
			}
		}

		unset($this->calledMethodStack[$stackName]);

		$this->calledMethodResults[$stackName] = $calledMethodEndScope;

		return $calledMethodEndScope;
	}

	public function clearCalledMethodResults(): void
	{
		$this->calledMethodResults = [];
	}

	/**
	 * @param Node[]|Node|scalar|null $node
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processNodesForCalledMethod(NodeScopeResolver $nodeScopeResolver, $node, ExpressionResultStorage $storage, string $fileName, MethodReflection $methodReflection, callable $nodeCallback): void
	{
		if ($node instanceof Node) {
			$declaringClass = $methodReflection->getDeclaringClass();
			if (
				$node instanceof Node\Stmt\Class_
				&& isset($node->namespacedName)
				&& $declaringClass->getName() === (string) $node->namespacedName
				&& $declaringClass->getNativeReflection()->getStartLine() === $node->getStartLine()
			) {

				$stmts = $node->stmts;
				foreach ($stmts as $stmt) {
					if (!$stmt instanceof Node\Stmt\ClassMethod) {
						continue;
					}

					if ($stmt->name->toString() !== $methodReflection->getName()) {
						continue;
					}

					if ($stmt->getEndLine() - $stmt->getStartLine() > 50) {
						continue;
					}

					$scope = $this->scopeFactory->create(ScopeContext::create($fileName))->enterClass($declaringClass);
					$nodeScopeResolver->processStmtNode($stmt, $scope, $storage, $nodeCallback, StatementContext::createTopLevel());
				}
				return;
			}
			if ($node instanceof Node\Stmt\ClassLike) {
				return;
			}
			if ($node instanceof Node\FunctionLike) {
				return;
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->{$subNodeName};
				$this->processNodesForCalledMethod($nodeScopeResolver, $subNode, $storage, $fileName, $methodReflection, $nodeCallback);
			}
		} elseif (is_array($node)) {
			foreach ($node as $subNode) {
				$this->processNodesForCalledMethod($nodeScopeResolver, $subNode, $storage, $fileName, $methodReflection, $nodeCallback);
			}
		}
	}

}
