<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function strtolower;

/**
 * The walks of the methods of the classes being walked: the scope a method's walk ends
 * with is what calling the method does to $this. ClassLikeHandler walks the methods
 * called on $this before their callers.
 */
#[AutowiredService]
final class CalledMethodProcessor
{

	/** @var array<string, array<string, MethodReturnStatementsNode>> by class name and lowercase method name */
	private array $walkedMethods = [];

	/** @var array<string, array<string, MutatingScope|null>> by class name and lowercase method name */
	private array $exitScopes = [];

	/** @var array<string, array<string, array<string, array{Type, Type}|null>>> by class name and lowercase method name */
	private array $assignedTypes = [];

	public function rememberWalkedMethod(MethodReturnStatementsNode $node): void
	{
		$className = $node->getClassReflection()->getName();
		$methodName = strtolower($node->getMethodName());
		$this->walkedMethods[$className][$methodName] = $node;
		unset($this->exitScopes[$className][$methodName], $this->assignedTypes[$className][$methodName]);
	}

	/**
	 * The merged scope at the ends of the method's walk, null when it was not walked
	 * yet or never returns.
	 */
	public function getWalkedMethodExitScope(ClassReflection $classReflection, string $methodName): ?MutatingScope
	{
		$className = $classReflection->getName();
		$methodName = strtolower($methodName);
		if (!isset($this->walkedMethods[$className][$methodName])) {
			return null;
		}
		if (!array_key_exists($methodName, $this->exitScopes[$className] ?? [])) {
			$this->exitScopes[$className][$methodName] = $this->getExitScope($this->walkedMethods[$className][$methodName]);
		}

		return $this->exitScopes[$className][$methodName];
	}

	/**
	 * The types the method's walk saw assigned to the property of any object with that name,
	 * with their native types, by property name; a property with an assigned type that names
	 * the method's template types is null. Null when the method was not walked yet.
	 *
	 * @return array<string, array{Type, Type}|null>|null
	 */
	public function getWalkedMethodAssignedTypes(ClassReflection $classReflection, string $methodName): ?array
	{
		$className = $classReflection->getName();
		$methodName = strtolower($methodName);
		if (!isset($this->walkedMethods[$className][$methodName])) {
			return null;
		}
		if (isset($this->assignedTypes[$className][$methodName])) {
			return $this->assignedTypes[$className][$methodName];
		}

		$assignedTypes = [];
		foreach ($this->walkedMethods[$className][$methodName]->getImpurePoints() as $impurePoint) {
			$node = $impurePoint->getNode();
			if (!$node instanceof PropertyAssignNode) {
				continue;
			}
			$propertyFetch = $node->getPropertyFetch();
			if (!$propertyFetch instanceof PropertyFetch || !$propertyFetch->name instanceof Identifier) {
				continue;
			}
			$propertyName = $propertyFetch->name->toString();
			if (array_key_exists($propertyName, $assignedTypes) && $assignedTypes[$propertyName] === null) {
				continue;
			}
			$scope = $impurePoint->getScope();
			$type = $scope->getType($node->getAssignedExpr());
			if (TypeUtils::containsTemplateType($type)) {
				// the method's own template types mean nothing after the call
				$assignedTypes[$propertyName] = null;
				continue;
			}
			$nativeType = $scope->getNativeType($node->getAssignedExpr());
			$assignedTypes[$propertyName] = isset($assignedTypes[$propertyName])
				? [TypeCombinator::union($assignedTypes[$propertyName][0], $type), TypeCombinator::union($assignedTypes[$propertyName][1], $nativeType)]
				: [$type, $nativeType];
		}

		return $this->assignedTypes[$className][$methodName] = $assignedTypes;
	}

	public function clearCalledMethodResults(ClassReflection $classReflection): void
	{
		$className = $classReflection->getName();
		unset($this->walkedMethods[$className], $this->exitScopes[$className], $this->assignedTypes[$className]);
	}

	private function getExitScope(MethodReturnStatementsNode $returnStatement): ?MutatingScope
	{
		$exitScope = null;
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
			if ($exitScope === null) {
				$exitScope = $statementResult->getScope()->toWalkScope();
				continue;
			}

			$exitScope = $exitScope->mergeWith($statementResult->getScope()->toWalkScope());
		}
		foreach ($returnStatement->getReturnStatements() as $statement) {
			if ($exitScope === null) {
				$exitScope = $statement->getScope()->toWalkScope();
				continue;
			}

			$exitScope = $exitScope->mergeWith($statement->getScope()->toWalkScope());
		}

		return $exitScope;
	}

}
