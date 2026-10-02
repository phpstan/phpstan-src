<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use function array_pop;
use function count;
use function spl_object_id;

/**
 * What a piece of analysed code depends on besides itself - the classes, functions and constants it
 * uses, the files it includes - so that the result cache analyses it again when they change.
 *
 * The expression and statement handlers put what they depend on to the ExpressionResult and
 * InternalStatementResult they return, together with what the results of their parts depend on.
 * Merging does not copy anything: it only points to the merged values, and the whole tree is walked
 * once, by DependencyResolver, when the analysis of a file is finished.
 *
 * Each value is bound to the file of the scope it was found in: the code of a trait is analysed in the
 * context of the class using it, and what is in the trait's own file is not a dependency of it.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/Dependencies.cpp')]
final class Dependencies
{

	/**
	 * @param list<Type> $types
	 * @param list<string> $classNames
	 * @param list<FunctionReflection|ConstantReflection> $reflections
	 * @param list<string> $filePaths
	 * @param list<ClassReflection> $usedTraits
	 * @param list<self> $merged
	 */
	private function __construct(
		private string $file,
		private array $types,
		private array $classNames,
		private array $reflections,
		private array $filePaths,
		private array $usedTraits,
		private array $merged,
	)
	{
	}

	/**
	 * Null when there is nothing to depend on.
	 *
	 * @param list<Type|null> $types the classes the types reference
	 * @param list<string> $classNames
	 * @param list<FunctionReflection|ConstantReflection> $reflections
	 * @param list<string> $filePaths files depended on by path - an included file holds no symbol to reflect
	 * @param list<ClassReflection> $usedTraits traits a class uses, analysed as a part of it
	 */
	public static function create(
		string $file,
		array $types = [],
		array $classNames = [],
		array $reflections = [],
		array $filePaths = [],
		array $usedTraits = [],
	): ?self
	{
		$nonNullTypes = [];
		foreach ($types as $type) {
			if ($type === null) {
				continue;
			}

			$nonNullTypes[] = $type;
		}

		if ($nonNullTypes === [] && $classNames === [] && $reflections === [] && $filePaths === [] && $usedTraits === []) {
			return null;
		}

		return new self($file, $nonNullTypes, $classNames, $reflections, $filePaths, $usedTraits, []);
	}

	public static function merge(?self ...$dependencies): ?self
	{
		$merged = [];
		foreach ($dependencies as $dependency) {
			if ($dependency === null) {
				continue;
			}

			$merged[] = $dependency;
		}

		if ($merged === []) {
			return null;
		}
		if (count($merged) === 1) {
			return $merged[0];
		}

		return new self('', [], [], [], [], [], $merged);
	}

	/**
	 * Calls the callback for every value in the tree once, even when the same value was merged more
	 * than once.
	 *
	 * @param callable(string $file, list<Type> $types, list<string> $classNames, list<FunctionReflection|ConstantReflection> $reflections, list<string> $filePaths, list<ClassReflection> $usedTraits): void $callback
	 */
	public function walk(callable $callback): void
	{
		$seen = [];
		$stack = [$this];
		while ($stack !== []) {
			$dependencies = array_pop($stack);
			$id = spl_object_id($dependencies);
			if (isset($seen[$id])) {
				continue;
			}
			$seen[$id] = true;

			if ($dependencies->merged !== []) {
				foreach ($dependencies->merged as $merged) {
					$stack[] = $merged;
				}
				continue;
			}

			$callback($dependencies->file, $dependencies->types, $dependencies->classNames, $dependencies->reflections, $dependencies->filePaths, $dependencies->usedTraits);
		}
	}

}
