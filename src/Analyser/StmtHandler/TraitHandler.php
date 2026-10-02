<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements StmtHandler<Trait_>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/TraitHandler.cpp')]
final class TraitHandler implements StmtHandler
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Trait_;
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
		// declaring the trait defines it in global state,
		// so a negative trait_exists() narrowing that may refer to that trait must be forgotten
		$name = $stmt->namespacedName ?? $stmt->name;
		$scope = $scope->invalidateExistenceCheckExpressions(['trait_exists'], $name instanceof Name ? $name->toString() : null);

		// the interfaces a class using the trait has to implement
		$dependencies = null;
		if ($stmt->namespacedName !== null && $this->reflectionProvider->hasClass($stmt->namespacedName->toString())) {
			$requiredTypes = [];
			foreach ($this->reflectionProvider->getClass($stmt->namespacedName->toString())->getRequireImplementsTags() as $implementsTag) {
				$requiredTypes[] = $implementsTag->getType();
			}
			$dependencies = Dependencies::create($scope->getFile(), $requiredTypes);
		}

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: [], dependencies: $dependencies);
	}

}
