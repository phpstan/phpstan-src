<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\CalledMethodProcessor;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use PHPStan\Node\ClassConstantsNode;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\ClassStatementsGatherer;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use function sprintf;
use function usort;
use const PHP_VERSION_ID;

/**
 * Trait_ has its own handler; every other ClassLike subtype lands here.
 *
 * @implements StmtHandler<ClassLike>
 */
#[AutowiredService]
final class ClassLikeHandler implements StmtHandler
{

	public function __construct(
		private CalledMethodProcessor $calledMethodProcessor,
		private ReflectionProvider $reflectionProvider,
		private Reflector $reflector,
		private ClassReflectionFactory $classReflectionFactory,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof ClassLike && !$stmt instanceof Trait_;
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
		// declaring a class/interface/enum defines it in global state,
		// so a matching negative existence-check narrowing must be forgotten
		if ($stmt instanceof Node\Stmt\Interface_) {
			$existenceCheckFunctionNames = ['interface_exists'];
		} elseif ($stmt instanceof Node\Stmt\Enum_) {
			$existenceCheckFunctionNames = ['class_exists', 'enum_exists'];
		} else {
			$existenceCheckFunctionNames = ['class_exists'];
		}
		$name = $stmt->namespacedName ?? $stmt->name;
		$scope = $scope->invalidateExistenceCheckExpressions($existenceCheckFunctionNames, $name instanceof Name ? $name->toString() : null);

		if (!$context->isTopLevel()) {
			return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
		}
		if (isset($stmt->namespacedName)) {
			$classReflection = $this->getCurrentClassReflection($nodeScopeResolver, $stmt, $stmt->namespacedName->toString(), $scope);
			$classScope = $scope->enterClass($classReflection);
			$nodeScopeResolver->callNodeCallback($nodeCallback, new InClassNode($stmt, $classReflection), $classScope, $storage);
		} elseif ($stmt instanceof Class_) {
			if ($stmt->name === null) {
				throw new ShouldNotHappenException();
			}
			if (!$stmt->isAnonymous()) {
				$classReflection = $this->reflectionProvider->getClass($stmt->name->toString());
			} else {
				$classReflection = $this->reflectionProvider->getAnonymousClassReflection($stmt, $scope);
			}
			$classScope = $scope->enterClass($classReflection);
			$nodeScopeResolver->callNodeCallback($nodeCallback, new InClassNode($stmt, $classReflection), $classScope, $storage);
		} else {
			throw new ShouldNotHappenException();
		}

		$classStatementsGatherer = new ClassStatementsGatherer($classReflection, $nodeCallback);
		$nodeScopeResolver->processAttributeGroups($stmt, $stmt->attrGroups, $classScope, $storage, $classStatementsGatherer);

		$classLikeStatements = $stmt->stmts;
		// analyze static methods first; constructor next; instance methods and property hooks last so we can carry over the scope
		usort($classLikeStatements, static function ($a, $b) {
			if ($a instanceof Node\Stmt\Property) {
				return 1;
			}
			if ($b instanceof Node\Stmt\Property) {
				return -1;
			}

			if (!$a instanceof Node\Stmt\ClassMethod || !$b instanceof Node\Stmt\ClassMethod) {
				return 0;
			}

			return [!$a->isStatic(), $a->name->toLowerString() !== '__construct'] <=> [!$b->isStatic(), $b->name->toLowerString() !== '__construct'];
		});

		$nodeScopeResolver->processStmtNodesInternal($stmt, $classLikeStatements, $classScope, $storage, $classStatementsGatherer, $context);
		$nodeScopeResolver->callNodeCallback($nodeCallback, new ClassPropertiesNode($stmt, $nodeScopeResolver->getReadWritePropertiesExtensions(), $classStatementsGatherer->getProperties(), $classStatementsGatherer->getPropertyUsages(), $classStatementsGatherer->getMethodCalls(), $classStatementsGatherer->getReturnStatementsNodes(), $classStatementsGatherer->getPropertyAssigns(), $classReflection), $classScope, $storage);
		$nodeScopeResolver->callNodeCallback($nodeCallback, new ClassMethodsNode($stmt, $classStatementsGatherer->getMethods(), $classStatementsGatherer->getMethodCalls(), $classReflection), $classScope, $storage);
		$nodeScopeResolver->callNodeCallback($nodeCallback, new ClassConstantsNode($stmt, $classStatementsGatherer->getConstants(), $classStatementsGatherer->getConstantFetches(), $classReflection), $classScope, $storage);
		$classReflection->evictPrivateSymbols();
		$this->calledMethodProcessor->clearCalledMethodResults();

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
	}

	private function getCurrentClassReflection(NodeScopeResolver $nodeScopeResolver, Node\Stmt\ClassLike $stmt, string $className, Scope $scope): ClassReflection
	{
		if (!$this->reflectionProvider->hasClass($className)) {
			return $this->createAstClassReflection($stmt, $className, $scope);
		}

		$defaultClassReflection = $this->reflectionProvider->getClass($className);
		if ($defaultClassReflection->getFileName() !== $scope->getFile()) {
			return $this->createAstClassReflection($stmt, $className, $scope);
		}

		$startLine = $defaultClassReflection->getNativeReflection()->getStartLine();
		if ($startLine !== $stmt->getStartLine()) {
			return $this->createAstClassReflection($stmt, $className, $scope);
		}

		return $defaultClassReflection;
	}

	private function createAstClassReflection(Node\Stmt\ClassLike $stmt, string $className, Scope $scope): ClassReflection
	{
		$nodeToReflection = new NodeToReflection();
		$betterReflectionClass = $nodeToReflection->__invoke(
			$this->reflector,
			$stmt,
			new LocatedSource(FileReader::read($scope->getFile()), $className, $scope->getFile()),
			$scope->getNamespace() !== null ? new Node\Stmt\Namespace_(new Name($scope->getNamespace())) : null,
		);
		if (!$betterReflectionClass instanceof \PHPStan\BetterReflection\Reflection\ReflectionClass) {
			throw new ShouldNotHappenException();
		}

		return $this->classReflectionFactory->create(
			$betterReflectionClass->getName(),
			$betterReflectionClass instanceof ReflectionEnum && PHP_VERSION_ID >= 80000
				? new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum($betterReflectionClass)
				: new ReflectionClass($betterReflectionClass),
			null,
			null,
			null,
			sprintf('%s:%d', $scope->getFile(), $stmt->getStartLine()),
		);
	}

}
