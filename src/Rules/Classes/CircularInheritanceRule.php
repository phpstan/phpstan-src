<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function array_slice;
use function count;
use function sprintf;
use function strtolower;

/**
 * A class that extends itself, interfaces that extend each other and traits that use each other
 * cannot be declared in PHP. Reflection treats a class on such a cycle as having no parent, and
 * this rule reports the cycle on each of its members.
 *
 * @implements Rule<Node\Stmt\ClassLike>
 */
#[RegisteredRule(level: 0)]
final class CircularInheritanceRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassLike::class;
	}

	public function processNode(Node $node, Scope&DependencyTracker $scope): array
	{
		if ($node->namespacedName === null) {
			return [];
		}

		if ($node instanceof Node\Stmt\Class_) {
			$kind = 'Class';
			$verb = 'extends';
			$identifier = 'class.circularExtends';
			$edges = static fn (ClassReflection $classReflection): array => self::parentClassNames($classReflection);
			$isOfKind = static fn (ClassReflection $classReflection): bool => $classReflection->isClass();
		} elseif ($node instanceof Node\Stmt\Interface_) {
			$kind = 'Interface';
			$verb = 'extends';
			$identifier = 'interface.circularExtends';
			$edges = static fn (ClassReflection $classReflection): array => $classReflection->getNativeReflection()->getBetterReflection()->getInterfaceClassNames();
			$isOfKind = static fn (ClassReflection $classReflection): bool => $classReflection->isInterface();
		} elseif ($node instanceof Node\Stmt\Trait_) {
			$kind = 'Trait';
			$verb = 'uses';
			$identifier = 'traitUse.circular';
			$edges = static fn (ClassReflection $classReflection): array => $classReflection->getNativeReflection()->getBetterReflection()->getTraitClassNames();
			$isOfKind = static fn (ClassReflection $classReflection): bool => $classReflection->isTrait();
		} else {
			return [];
		}

		$className = $node->namespacedName->toString();

		// a trait declaration is not analysed on its own, so its file does not depend on the traits
		// it uses; these edges re-analyse it when a used trait closes a cycle further up
		if ($node instanceof Node\Stmt\Trait_ && $this->reflectionProvider->hasClass($className)) {
			foreach ($edges($this->reflectionProvider->getClass($className)) as $usedTraitName) {
				$scope->trackClassDependency($usedTraitName);
			}
		}

		$cycle = $this->findCycle($className, $edges, $isOfKind);
		if ($cycle === null) {
			return [];
		}

		// without a cycle the file depends on all the ancestors already, so closing a cycle further
		// up re-analyses it; on a cycle the ancestors are cut, so breaking it needs these edges
		foreach (array_slice($cycle, 1, -1) as $cycleMemberName) {
			$scope->trackClassDependency($cycleMemberName);
		}

		if (count($cycle) === 2) {
			$message = sprintf('%s %s %s itself.', $kind, $cycle[0], $verb);
		} else {
			$message = sprintf('%s %s %s %s', $kind, $cycle[0], $verb, $cycle[1]);
			foreach (array_slice($cycle, 2) as $className) {
				$message .= sprintf(', which %s %s', $verb, $className);
			}
			$message .= '.';
		}

		return [
			RuleErrorBuilder::message($message)
				->identifier($identifier)
				->nonIgnorable()
				->build(),
		];
	}

	/**
	 * @return list<string>
	 */
	private static function parentClassNames(ClassReflection $classReflection): array
	{
		$parentClassName = $classReflection->getNativeReflection()->getBetterReflection()->getParentClassName();
		if ($parentClassName === null) {
			return [];
		}

		return [$parentClassName];
	}

	/**
	 * Follows the declared edges from $className depth first and returns the path back to it,
	 * starting and ending with $className. Only edges to class-likes of the same kind count.
	 *
	 * @param callable(ClassReflection): list<string> $edges
	 * @param callable(ClassReflection): bool $isOfKind
	 * @return list<string>|null
	 */
	private function findCycle(string $className, callable $edges, callable $isOfKind): ?array
	{
		$visited = [];

		return $this->findPathBack($className, $className, [$className], $visited, $edges, $isOfKind);
	}

	/**
	 * @param list<string> $path
	 * @param array<string, true> $visited
	 * @param callable(ClassReflection): list<string> $edges
	 * @param callable(ClassReflection): bool $isOfKind
	 * @return list<string>|null
	 */
	private function findPathBack(
		string $className,
		string $currentClassName,
		array $path,
		array &$visited,
		callable $edges,
		callable $isOfKind,
	): ?array
	{
		if (!$this->reflectionProvider->hasClass($currentClassName)) {
			return null;
		}

		foreach ($edges($this->reflectionProvider->getClass($currentClassName)) as $nextClassName) {
			$lowercasedNextClassName = strtolower($nextClassName);
			if ($lowercasedNextClassName === strtolower($className)) {
				$path[] = $className;
				return $path;
			}

			if (array_key_exists($lowercasedNextClassName, $visited)) {
				continue;
			}
			$visited[$lowercasedNextClassName] = true;

			if (!$this->reflectionProvider->hasClass($nextClassName)) {
				continue;
			}

			$nextClassReflection = $this->reflectionProvider->getClass($nextClassName);
			if (!$isOfKind($nextClassReflection)) {
				continue;
			}

			$nextPath = $path;
			$nextPath[] = $nextClassReflection->getName();
			$cycle = $this->findPathBack($className, $nextClassName, $nextPath, $visited, $edges, $isOfKind);
			if ($cycle !== null) {
				return $cycle;
			}
		}

		return null;
	}

}
