<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileHelper;
use PHPStan\Node\InTraitNode;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function in_array;
use function is_array;

/**
 * @implements StmtHandler<TraitUse>
 */
#[AutowiredService]
final class TraitUseHandler implements StmtHandler
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private FileHelper $fileHelper,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private Parser $parser,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof TraitUse;
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
		// fresh storage - the same trait node objects are processed once per
		// using class must not see results from a previous pass
		$traitStorage = new ExpressionResultStorage();
		$scope->pushExpressionResultStorage($traitStorage);
		try {
			$this->processTraitUse($nodeScopeResolver, $stmt, $scope, $traitStorage, $nodeCallback);
		} finally {
			$scope->popExpressionResultStorage();
		}

		// class-level node callbacks (like ClassMethodsNode) are invoked with
		// the outer storage but ask about expressions inside the used trait
		$storage->mergeResults($traitStorage);

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processTraitUse(NodeScopeResolver $nodeScopeResolver, Node\Stmt\TraitUse $node, MutatingScope $classScope, ExpressionResultStorage $storage, callable $nodeCallback): void
	{
		$parentTraitNames = [];
		$parent = $classScope->getParentScope();
		while ($parent !== null) {
			if ($parent->isInTrait()) {
				$parentTraitNames[] = $parent->getTraitReflection()->getName();
			}
			$parent = $parent->getParentScope();
		}

		foreach ($node->traits as $trait) {
			$traitName = (string) $trait;
			if (in_array($traitName, $parentTraitNames, true)) {
				continue;
			}
			if (!$this->reflectionProvider->hasClass($traitName)) {
				continue;
			}
			$traitReflection = $this->reflectionProvider->getClass($traitName);
			$traitFileName = $traitReflection->getFileName();
			if ($traitFileName === null) {
				continue; // trait from eval or from PHP itself
			}
			$fileName = $this->fileHelper->normalizePath($traitFileName);
			if (!$nodeScopeResolver->isAnalysedFile($fileName)) {
				continue;
			}
			$adaptations = [];
			foreach ($node->adaptations as $adaptation) {
				if ($adaptation->trait === null) {
					$adaptations[] = $adaptation;
					continue;
				}
				if ($adaptation->trait->toLowerString() !== $trait->toLowerString()) {
					continue;
				}

				$adaptations[] = $adaptation;
			}
			$parserNodes = $this->parser->parseFile($fileName);
			$this->processNodesForTraitUse($nodeScopeResolver, $parserNodes, $traitReflection, $classScope, $storage, $adaptations, $nodeCallback);
		}
	}

	/**
	 * @param Node[]|Node|scalar|null $node
	 * @param Node\Stmt\TraitUseAdaptation[] $adaptations
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processNodesForTraitUse(NodeScopeResolver $nodeScopeResolver, $node, ClassReflection $traitReflection, MutatingScope $scope, ExpressionResultStorage $storage, array $adaptations, callable $nodeCallback): void
	{
		if ($node instanceof Node) {
			if ($node instanceof Node\Stmt\Trait_ && $traitReflection->getName() === (string) $node->namespacedName && $traitReflection->getNativeReflection()->getStartLine() === $node->getStartLine()) {
				$methodModifiers = [];
				$methodNames = [];
				foreach ($adaptations as $adaptation) {
					if (!$adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
						continue;
					}

					$methodName = $adaptation->method->toLowerString();
					if ($adaptation->newModifier !== null) {
						$methodModifiers[$methodName] = $adaptation->newModifier;
					}

					if ($adaptation->newName === null) {
						continue;
					}

					$methodNames[$methodName] = $adaptation->newName;
				}

				$stmts = $node->stmts;
				foreach ($stmts as $i => $stmt) {
					if (!$stmt instanceof Node\Stmt\ClassMethod) {
						continue;
					}
					$methodName = $stmt->name->toLowerString();
					$methodAst = clone $stmt;
					$stmts[$i] = $methodAst;
					if (array_key_exists($methodName, $methodModifiers)) {
						$methodAst->flags = ($methodAst->flags & ~ Modifiers::VISIBILITY_MASK) | $methodModifiers[$methodName];
					}

					if (!array_key_exists($methodName, $methodNames)) {
						continue;
					}

					$methodAst->setAttribute('originalTraitMethodName', $methodAst->name->toLowerString());
					$methodAst->name = $methodNames[$methodName];
				}

				if (!$scope->isInClass()) {
					throw new ShouldNotHappenException();
				}
				$traitScope = $scope->enterTrait($traitReflection);

				// attribute args are not processed as part of the trait statements
				// but rules like TraitAttributesRule ask about their types
				$nodeScopeResolver->processAttributeGroups($node, $node->attrGroups, $traitScope, $storage, new NoopNodeCallback());

				$nodeScopeResolver->callNodeCallback($nodeCallback, new InTraitNode($node, $traitReflection, $scope->getClassReflection()), $traitScope, $storage);
				$nodeScopeResolver->processStmtNodesInternal($node, $stmts, $traitScope, $storage, $nodeCallback, StatementContext::createTopLevel());
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
				$this->processNodesForTraitUse($nodeScopeResolver, $subNode, $traitReflection, $scope, $storage, $adaptations, $nodeCallback);
			}
		} elseif (is_array($node)) {
			foreach ($node as $subNode) {
				$this->processNodesForTraitUse($nodeScopeResolver, $subNode, $traitReflection, $scope, $storage, $adaptations, $nodeCallback);
			}
		}
	}

}
