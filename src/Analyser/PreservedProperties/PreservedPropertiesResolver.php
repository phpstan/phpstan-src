<?php declare(strict_types = 1);

namespace PHPStan\Analyser\PreservedProperties;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\CalledMethodProcessor;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\PreservedPropertiesVisitor;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function array_keys;
use function count;
use function explode;
use function implode;
use function str_contains;

/**
 * Which properties of $this keep their remembered types across an impure call
 * on $this - what PreservedPropertiesVisitor found, minus the properties passed
 * to a by-reference parameter of a function.
 */
#[AutowiredService]
final class PreservedPropertiesResolver
{

	/** @var array<string, bool> */
	private array $byValueCache = [];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private CalledMethodProcessor $calledMethodProcessor,
	)
	{
	}

	/**
	 * After a call of a method of the class on $this that runs exactly that method - a private
	 * or final one, or one called through self:: - the properties the walk of the method ended
	 * with initialized are initialized, and the properties of $this the class's own code the call
	 * runs writes get the types the walk ended with, but the ones the class's closures write, which
	 * can run whenever. ClassLikeHandler walks the methods before
	 * their callers.
	 */
	public function applyCalledMethodExitScope(MutatingScope $scope, Expr $call, MethodReflection $methodReflection): MutatingScope
	{
		if ($methodReflection->isStatic() || !$scope->isInClass()) {
			return $scope;
		}
		$classReflection = $scope->getClassReflection();
		if ($methodReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
			return $scope;
		}

		if ($call instanceof MethodCall) {
			if (!$call->var instanceof Variable || $call->var->name !== 'this') {
				return $scope;
			}
			$runsExactly = false;
		} elseif ($call instanceof StaticCall) {
			if (!$call->class instanceof Name) {
				return $scope;
			}
			$className = $call->class->toLowerString();
			if ($className === 'parent') {
				return $scope;
			}
			$function = $scope->getFunction();
			if (!$function instanceof MethodReflection || $function->isStatic()) {
				return $scope;
			}
			$runsExactly = $className !== 'static';
		} else {
			return $scope;
		}
		if (!$runsExactly && !$methodReflection->isPrivate() && !$methodReflection->isFinal()->yes() && !$classReflection->isFinal()) {
			return $scope;
		}

		$exitScope = $this->calledMethodProcessor->getWalkedMethodExitScope($classReflection, $methodReflection->getName());
		if ($exitScope === null) {
			return $scope;
		}
		$scope = $scope->mergeInitializedProperties($exitScope);

		$preservedProperties = $call->getAttribute(PreservedPropertiesVisitor::ATTRIBUTE_NAME);
		if (!$preservedProperties instanceof PreservedProperties) {
			return $scope;
		}
		$writtenPropertyNames = $preservedProperties->getWrittenPropertyNames();

		$closureWrittenPropertyNames = $preservedProperties->getClosureWrittenPropertyNames();
		$overrideAssignedTypes = null;
		foreach (array_keys($writtenPropertyNames) as $propertyName) {
			$propertyFetch = new PropertyFetch(new Variable('this'), $propertyName);
			if (
				$scope->hasExpressionType($propertyFetch)->yes()
				|| !$exitScope->hasExpressionType($propertyFetch)->yes()
				|| isset($closureWrittenPropertyNames[$propertyName])
			) {
				continue;
			}
			$type = $exitScope->getType($propertyFetch);
			if (TypeUtils::containsTemplateType($type)) {
				// the method's own template types mean nothing after the call
				continue;
			}
			$nativeType = $exitScope->getNativeType($propertyFetch);
			$overrideAssignedTypes ??= $this->findOverrideAssignedTypes($scope, $preservedProperties);
			if (array_key_exists($propertyName, $overrideAssignedTypes)) {
				if ($overrideAssignedTypes[$propertyName] === null) {
					// not all the writes are seen
					continue;
				}
				$type = TypeCombinator::union($type, $overrideAssignedTypes[$propertyName][0]);
				$nativeType = TypeCombinator::union($nativeType, $overrideAssignedTypes[$propertyName][1]);
			}
			$scope = $scope->assignExpression($propertyFetch, $type, $nativeType);
		}

		return $scope;
	}

	/**
	 * The private properties the class's overrides of the non-private methods of its parent write,
	 * which the parent's code can run whenever it runs, each with the types they assign, or null
	 * when they write it in a way no walk sees the value of or their walks are not known yet.
	 *
	 * @return array<string, array{Type, Type}|null>
	 */
	private function findOverrideAssignedTypes(MutatingScope $scope, PreservedProperties $preservedProperties): array
	{
		$overrideWrittenPropertyNames = $preservedProperties->getOverrideWrittenPropertyNames();
		$classReflection = $scope->getClassReflection();
		if (count($overrideWrittenPropertyNames) === 0 || $classReflection === null) {
			return [];
		}

		$parentClassName = $preservedProperties->getParentClassName();
		$parentClassReflection = $parentClassName !== null && $this->reflectionProvider->hasClass($parentClassName)
			? $this->reflectionProvider->getClass($parentClassName)
			: null;
		$assignedTypes = [];
		foreach ($overrideWrittenPropertyNames as $methodName => $propertyNames) {
			if (
				$parentClassReflection !== null
				&& (!$parentClassReflection->hasNativeMethod($methodName) || $parentClassReflection->getNativeMethod($methodName)->isPrivate())
			) {
				continue;
			}
			foreach ($propertyNames as $propertyName => $writers) {
				if (array_key_exists($propertyName, $assignedTypes) && $assignedTypes[$propertyName] === null) {
					continue;
				}
				$byValue = $writers !== null && $this->areFunctionArgumentsByValue($writers[1]);
				if ($byValue && count($writers[0]) === 0) {
					// only passed to by-value parameters
					continue;
				}
				$assignedType = $byValue ? $this->findAssignedType($classReflection, $writers[0], $propertyName) : null;
				if ($assignedType === null || !isset($assignedTypes[$propertyName])) {
					$assignedTypes[$propertyName] = $assignedType;
					continue;
				}
				$assignedTypes[$propertyName] = [
					TypeCombinator::union($assignedTypes[$propertyName][0], $assignedType[0]),
					TypeCombinator::union($assignedTypes[$propertyName][1], $assignedType[1]),
				];
			}
		}

		return $assignedTypes;
	}

	/**
	 * @param list<array{list<non-empty-string>, int, string}> $functionArguments
	 */
	private function areFunctionArgumentsByValue(array $functionArguments): bool
	{
		foreach ($functionArguments as [$functionNames, $position]) {
			if (!$this->isFunctionArgumentByValue($functionNames, $position)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The union of the types the methods assign to the property, with its native type; null when
	 * some of them was not walked yet or its walk does not see the assignment.
	 *
	 * @param list<string> $writers
	 * @return array{Type, Type}|null
	 */
	private function findAssignedType(ClassReflection $classReflection, array $writers, string $propertyName): ?array
	{
		$types = [];
		$nativeTypes = [];
		foreach ($writers as $writer) {
			$assignedType = $this->calledMethodProcessor->getWalkedMethodAssignedTypes($classReflection, $writer)[$propertyName] ?? null;
			if ($assignedType === null) {
				return null;
			}
			$types[] = $assignedType[0];
			$nativeTypes[] = $assignedType[1];
		}
		if (count($types) === 0) {
			return null;
		}

		return [TypeCombinator::union(...$types), TypeCombinator::union(...$nativeTypes)];
	}

	/**
	 * @return array<string, true>|null null when every property is forgotten
	 */
	public function resolve(Expr $call): ?array
	{
		$preservedProperties = $call->getAttribute(PreservedPropertiesVisitor::ATTRIBUTE_NAME);
		if (!$preservedProperties instanceof PreservedProperties) {
			return null;
		}

		$propertyNames = $preservedProperties->getPropertyNames();
		foreach ($preservedProperties->getFunctionArguments() as [$functionNames, $position, $propertyName]) {
			if (!isset($propertyNames[$propertyName]) || $this->isFunctionArgumentByValue($functionNames, $position)) {
				continue;
			}
			unset($propertyNames[$propertyName]);
		}

		return count($propertyNames) > 0 ? $propertyNames : null;
	}

	/**
	 * A call not known to have side effects keeps everything remembered, except the properties
	 * of $this that PreservedPropertiesVisitor saw the class's own code run by the call write.
	 */
	public function forgetWrittenProperties(MutatingScope $scope, Expr $call): MutatingScope
	{
		$preservedProperties = $call->getAttribute(PreservedPropertiesVisitor::ATTRIBUTE_NAME);
		if (!$preservedProperties instanceof PreservedProperties) {
			return $scope;
		}

		$propertyNames = $preservedProperties->getWrittenPropertyNames();
		foreach ($preservedProperties->getWrittenFunctionArguments() as [$functionNames, $position, $propertyName]) {
			if ($this->isFunctionArgumentByValue($functionNames, $position)) {
				continue;
			}
			$propertyNames[$propertyName] = true;
		}

		foreach (array_keys($propertyNames) as $propertyName) {
			$propertyFetch = new PropertyFetch(new Variable('this'), $propertyName);
			if ($scope->hasExpressionType($propertyFetch)->yes() && $scope->isReadonlyPropertyFetch($propertyFetch, true)) {
				// remembered, so initialized, and a readonly property cannot change after that
				continue;
			}
			$scope = $scope->invalidateExpression($propertyFetch);
		}

		return $scope;
	}

	/**
	 * @param list<non-empty-string> $functionNames
	 */
	private function isFunctionArgumentByValue(array $functionNames, int $position): bool
	{
		return $this->byValueCache[implode('|', $functionNames) . '#' . $position] ??= $this->findIsFunctionArgumentByValue($functionNames, $position);
	}

	/**
	 * @param list<non-empty-string> $functionNames candidate function names, or 'Class::method'
	 */
	private function findIsFunctionArgumentByValue(array $functionNames, int $position): bool
	{
		foreach ($functionNames as $functionName) {
			$variants = $this->findVariants($functionName);
			if ($variants === null) {
				continue;
			}

			foreach ($variants as $variant) {
				$parameters = $variant->getParameters();
				$parameter = $parameters[$position] ?? null;
				if ($parameter === null) {
					if (!$variant->isVariadic() || count($parameters) === 0) {
						continue;
					}
					$parameter = $parameters[count($parameters) - 1];
				}
				if (!$parameter->passedByReference()->no()) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @param non-empty-string $calleeName
	 * @return list<ParametersAcceptor>|null null when there is no such function or method
	 */
	private function findVariants(string $calleeName): ?array
	{
		if (!str_contains($calleeName, '::')) {
			$name = new Name($calleeName);
			if (!$this->reflectionProvider->hasFunction($name, null)) {
				return null;
			}

			return $this->reflectionProvider->getFunction($name, null)->getVariants();
		}

		[$className, $methodName] = explode('::', $calleeName, 2);
		if (!$this->reflectionProvider->hasClass($className)) {
			return null;
		}
		$classReflection = $this->reflectionProvider->getClass($className);
		if (!$classReflection->hasNativeMethod($methodName)) {
			// a class without a constructor takes no arguments by reference
			return $methodName === '__construct' ? [] : null;
		}

		return $classReflection->getNativeMethod($methodName)->getVariants();
	}

}
