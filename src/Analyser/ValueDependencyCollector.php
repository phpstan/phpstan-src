<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Analyser\ResultCache\ClassResultCacheValueExtension;
use PHPStan\Analyser\ResultCache\ConstantResultCacheValueExtension;
use PHPStan\Analyser\ResultCache\DirectoryResultCacheValueExtension;
use PHPStan\Analyser\ResultCache\FileResultCacheValueExtension;
use PHPStan\Analyser\ResultCache\FunctionResultCacheValueExtension;
use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\File\FileHelper;
use PHPStan\Reflection\ClassNameHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ReferencedByTurboExtension;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function ltrim;
use function rtrim;
use function sprintf;
use function str_starts_with;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Collects the values declared through DependencyTracker::trackValueDependency() while a file is being
 * analysed, and what each value was at that moment - the value the analysis saw.
 *
 * A value is identified by its extension and key: the same one declared again, by the same rule
 * or another one, or by another file, is the same dependency, recorded once. Two kinds of
 * dependents are recorded for it:
 *
 * - "analysis": what is reported in the file being analysed. Its analysis read the value, so a
 *   change of it has to re-analyse this file.
 * - "declarations": what a file declares - the file of the scope, when the dependency is declared outside the walk of
 *   the analysed file, on a scope without a node callback. PHPStan steps outside of it to infer
 *   what a file declares - the type of a private property without a type is inferred from the
 *   constructor of its class, on a scope of the class's file, whenever the property is first
 *   needed, during the analysis of whichever file that is. The inferred type is cached and reused
 *   by the files analysed after it, so it is what the scope's file declares that depends on the
 *   value: a change of it re-analyses the scope's file and every file depending on it.
 *
 * AnalysedFileDependencyCollector and AnalysedDeclarationDependencyCollector make the same split for
 * the files an analysis of only some files has to include to report unmatched ignoreErrors entries.
 *
 * A rule for CollectedDataNode runs outside the analysis of a file. What it declares is collected
 * for the rule itself, between startCollectedDataRule() and finishCollectedDataRule(), and stored
 * with its errors: the next run reuses them only while every value is still the same. Outside of
 * both, nothing is collected.
 *
 * DeclarationDependencyTracker records what a class declares depends on, through extensions that
 * describe a class rather than analyse code. Their result is remembered for the rest of the process
 * and reused by every file analysed after it without asking the extension again, so these are kept
 * for the whole process too, by class, and every file depending on the class - or on a class
 * extending it or implementing it - gets them as its own dependencies when its analysis finishes.
 * A rule for CollectedDataNode gets those of every class, because it is not known which classes
 * it read.
 *
 * Every class, function and constant the reflection layer is asked about is recorded too, found or
 * not - the result of whatever asked would be different once it appears or goes away, and the
 * analysed code does not always name it, it can come from a string:
 *
 * - not found, see recordMissingSymbol(): a value dependency, "missing" until it is declared
 * - found, see recordFoundSymbol(): a dependency on the file declaring it, like on the files
 *   declaring the symbols the analysed code references, see getFoundSymbols()
 *
 * Both are split like the values are: looked up during the analysis of the file, what is reported in
 * it depends on them; looked up while resolving a declaration, see enterDeclaration(), what the
 * declaration declares depends on them, and so does every file depending on the declaration.
 *
 * The lookups are recorded per context - the analysed file and the declarations being resolved,
 * see enterDeclaration() - and getContext() changes with it: the reflection layer remembers its
 * answers, and asks to record a remembered one again only in a context it has not recorded it in
 * yet.
 *
 * @phpstan-type ValueDependencies = array{
 *     values: array<string, array{string, string, string}>,
 *     dependents: array<string, array{analysis: list<string>, declarations: list<string>}>,
 * }
 */
#[AutowiredService]
#[ReferencedByTurboExtension(key: 'valueDependencyCollector')]
final class ValueDependencyCollector
{

	public const KIND_CLASS = 'class';

	public const KIND_FUNCTION = 'function';

	public const KIND_CONSTANT = 'constant';

	/** the value of a class, a function or a constant that does not exist */
	public const MISSING = 'missing';

	private ?string $analysedFile = null;

	/**
	 * While a rule for CollectedDataNode runs. A value is null until getCollectedDataRuleValues() asks
	 * for it: most of them are never needed, as the result of a rule reading no collected data is not
	 * kept, and the value of a class found means parsing the files declaring it and its ancestors.
	 *
	 * @var array<string, array{string, string, string|null}>|null id => [extension class, key, value]
	 */
	private ?array $collectedDataRuleValues = null;

	/** @var array<string, array{string, string, string}> id => [extension class, key, value] */
	private array $values = [];

	/** @var array<string, array{analysis: array<string, true>, declarations: array<string, true>}> dependent file => ids */
	private array $dependents = [];

	/** @var array<string, array<string, array{string, string, string}>> lowercase class name => id => [extension class, key, value], for the whole process */
	private array $classDeclarations = [];

	/** @var array<string, true> lowercase class names the analysed file depends on, with their ancestors */
	private array $dependedOnClasses = [];

	/** @var list<array{string|null, string|null}> the files and the keys of the declarations being resolved, see enterDeclaration() */
	private array $declarations = [];

	/** @var array<string, array<string, true>> file => keys of the declarations resolved in it, for the whole process */
	private array $fileDeclarationKeys = [];

	/** @var array<self::KIND_*, array<string, string>> kind => lowercase name => name, found during the analysis of the file */
	private array $analysisFoundSymbols = [];

	/** @var array<string, array<self::KIND_*, array<string, string>>> declaration key => kind => lowercase name => name, found while resolving the declaration, for the whole process */
	private array $declarationFoundSymbols = [];

	/** see getContext() */
	private static int $context = 0;

	/**
	 * @param ExtensionsCollection<ResultCacheValueExtension> $valueExtensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: ResultCacheValueExtension::class)]
		private ExtensionsCollection $valueExtensions,
		private FileHelper $fileHelper,
	)
	{
	}

	/**
	 * Changes whenever what a lookup is recorded for does: another file is analysed, or the
	 * declarations of another file are being resolved. A cache of lookups remembers it with each
	 * answer and asks to record the answer again only when it is different.
	 */
	public static function getContext(): int
	{
		return self::$context;
	}

	public static function getId(string $extensionClass, string $key): string
	{
		return $extensionClass . "\0" . $key;
	}

	public function startFile(string $analysedFile): void
	{
		$this->analysedFile = $analysedFile;
		$this->values = [];
		$this->dependents = [$analysedFile => ['analysis' => [], 'declarations' => []]];
		$this->dependedOnClasses = [];
		$this->analysisFoundSymbols = [];
		self::$context++;
	}

	/**
	 * @param class-string<ResultCacheValueExtension> $extensionClass
	 */
	public function record(string $extensionClass, string $key, Scope $scope, bool $insideWalk): void
	{
		if ($this->collectedDataRuleValues !== null) {
			$this->collectedDataRuleValues[self::getId($extensionClass, $key)] ??= [$extensionClass, $key, null];
			return;
		}

		if ($this->analysedFile === null) {
			return;
		}

		$id = self::getId($extensionClass, $key);
		if (!array_key_exists($id, $this->values)) {
			$this->values[$id] = [$extensionClass, $key, $this->getRegisteredExtension($extensionClass)->getValue($key)];
		}

		$this->dependents[$this->analysedFile]['analysis'][$id] = true;
		if ($insideWalk) {
			return;
		}

		$scopeFile = $scope->getFile();
		if (!array_key_exists($scopeFile, $this->dependents)) {
			$this->dependents[$scopeFile] = ['analysis' => [], 'declarations' => []];
		}
		$this->dependents[$scopeFile]['declarations'][$id] = true;
	}

	/**
	 * DependencyTracker::trackFileDependency() - a dependency on the contents of a file, through
	 * FileResultCacheValueExtension.
	 */
	public function recordFile(string $file, Scope $scope, bool $insideWalk): void
	{
		$file = $this->getFileKey($file);
		if ($insideWalk && $file === $this->analysedFile) {
			// the analysed file is re-analysed when it changes anyway
			return;
		}

		$this->record(FileResultCacheValueExtension::class, $file, $scope, $insideWalk);
	}

	/**
	 * DependencyTracker::trackDirectoryDependency() - a dependency on the files in a directory, through
	 * DirectoryResultCacheValueExtension.
	 */
	public function recordDirectory(string $directory, string $pattern, Scope $scope, bool $insideWalk): void
	{
		$this->record(DirectoryResultCacheValueExtension::class, $this->getDirectoryKey($directory, $pattern), $scope, $insideWalk);
	}

	/**
	 * DependencyTracker::trackClassDependency() - a dependency on what a class declares, through
	 * ClassResultCacheValueExtension.
	 */
	public function recordClass(string $className, Scope $scope, bool $insideWalk): void
	{
		$this->record(ClassResultCacheValueExtension::class, ClassResultCacheValueExtension::createKey($className), $scope, $insideWalk);
	}

	public function startCollectedDataRule(): void
	{
		$this->collectedDataRuleValues = [];
		self::$context++;
	}

	/**
	 * With what every class seen in this process declares depends on, see recordForClass(): the
	 * rule may have read any of them, and an extension describes a class only once per process.
	 *
	 * @return array<string, array{string, string, string}> id => [extension class, key, value]
	 */
	public function getCollectedDataRuleValues(): array
	{
		if ($this->collectedDataRuleValues === null) {
			return [];
		}

		// getting a value can look up more symbols, which are recorded for the rule too
		do {
			$computed = false;
			foreach ($this->collectedDataRuleValues as $id => [$extensionClass, $key, $value]) {
				if ($value !== null) {
					continue;
				}

				$this->collectedDataRuleValues[$id][2] = $this->getRegisteredExtension($extensionClass)->getValue($key);
				$computed = true;
			}
		} while ($computed);

		/** @var array<string, array{string, string, string}> $values */
		$values = $this->collectedDataRuleValues;
		foreach ($this->classDeclarations as $classValues) {
			$values += $classValues;
		}

		return $values;
	}

	public function finishCollectedDataRule(): void
	{
		$this->collectedDataRuleValues = null;
		self::$context++;
	}

	public function getFileKey(string $file): string
	{
		return $this->fileHelper->normalizePath($file);
	}

	public function getDirectoryKey(string $directory, string $pattern): string
	{
		return DirectoryResultCacheValueExtension::createKey(rtrim($this->fileHelper->normalizePath($directory), '/\\'), $pattern);
	}

	/**
	 * DeclarationDependencyTracker - what the class declares depends on the value $extensionClass
	 * gives for $key. Its value is the one seen first in this process, like the declaration itself.
	 *
	 * @param class-string<ResultCacheValueExtension> $extensionClass
	 */
	public function recordForClass(string $className, string $extensionClass, string $key): void
	{
		$id = self::getId($extensionClass, $key);
		$className = strtolower($className);
		if (array_key_exists($id, $this->classDeclarations[$className] ?? [])) {
			return;
		}

		$this->classDeclarations[$className][$id] = [$extensionClass, $key, $this->getRegisteredExtension($extensionClass)->getValue($key)];
	}

	/**
	 * A lookup of a class, a function or a constant found nothing. The analysed file - or the rule
	 * for CollectedDataNode running - depends on it not existing, and so does what the files passed
	 * to enterDeclaration() declare: the answer is remembered with their reflection, and a file
	 * asking the same later gets it without the lookup.
	 *
	 * Called from the reflection layer, which has no Scope. The value is known without asking the
	 * extension, which would look the symbol up again.
	 *
	 * @param self::KIND_* $kind
	 */
	public function recordMissingSymbol(string $kind, string $name): void
	{
		if (!self::isValidSymbolName($kind, $name)) {
			// can never be declared
			return;
		}

		[$extensionClass, $key] = self::getExtensionAndKey($kind, $name);
		$id = self::getId($extensionClass, $key);
		$value = [$extensionClass, $key, self::MISSING];
		if ($this->collectedDataRuleValues !== null) {
			$this->collectedDataRuleValues[$id] ??= $value;
			return;
		}

		if ($this->analysedFile === null) {
			return;
		}

		$this->values[$id] ??= $value;
		$this->dependents[$this->analysedFile]['analysis'][$id] = true;
		foreach ($this->declarations as [$declaringFile]) {
			if ($declaringFile === null) {
				// a built-in symbol, nothing to re-analyse
				continue;
			}
			if (!array_key_exists($declaringFile, $this->dependents)) {
				$this->dependents[$declaringFile] = ['analysis' => [], 'declarations' => []];
			}
			$this->dependents[$declaringFile]['declarations'][$id] = true;
		}
	}

	/**
	 * A lookup of a class, a function or a constant found it. The analysed file depends on the file
	 * declaring it, and so do the files depending on the declarations passed to enterDeclaration(),
	 * see getFoundSymbols(): when it goes away, they are re-analysed.
	 *
	 * A rule for CollectedDataNode, whose result is reused while the collected data it read stay the
	 * same, depends on the value of the symbol instead: the symbol can go away without them
	 * changing.
	 *
	 * @param self::KIND_* $kind
	 * @param string $name the name it was found by, without a leading backslash
	 */
	public function recordFoundSymbol(string $kind, string $name): void
	{
		if ($this->collectedDataRuleValues !== null) {
			[$extensionClass, $key] = self::getExtensionAndKey($kind, $name);
			$this->collectedDataRuleValues[self::getId($extensionClass, $key)] ??= [$extensionClass, $key, null];
			return;
		}

		if ($this->analysedFile === null) {
			return;
		}

		$lowerName = strtolower($name);
		$this->analysisFoundSymbols[$kind][$lowerName] = $name;
		foreach ($this->declarations as [, $declarationKey]) {
			if ($declarationKey === null) {
				continue;
			}
			$this->declarationFoundSymbols[$declarationKey][$kind][$lowerName] = $name;
		}
	}

	/**
	 * The symbols found during the analysis of the file - with $includeAnalysis - and while
	 * resolving the declarations it depends on, by kind - the files declaring them are what the
	 * analysed file depends on too.
	 * $declarationKeys are the keys of the declarations the analysed file depends on so far - see
	 * getClassDeclarationKey(), getFunctionDeclarationKey(), getFileDeclarationKey() and
	 * getDeclarationKeysOfFile(); those of what is returned are to be passed again, until nothing
	 * new is returned.
	 *
	 * @param list<string> $declarationKeys
	 * @return array<self::KIND_*, list<string>>
	 */
	public function getFoundSymbols(array $declarationKeys, bool $includeAnalysis): array
	{
		$symbols = $includeAnalysis ? $this->analysisFoundSymbols : [];
		foreach ($declarationKeys as $declarationKey) {
			foreach ($this->declarationFoundSymbols[$declarationKey] ?? [] as $kind => $names) {
				$symbols[$kind] = ($symbols[$kind] ?? []) + $names;
			}
		}

		return array_map(static fn (array $names): array => array_values($names), $symbols);
	}

	/**
	 * What a class declares, with what PHPDocs of its members declare - a file depending on the class
	 * or on a class extending it depends on it.
	 */
	public static function getClassDeclarationKey(string $className): string
	{
		return 'class ' . strtolower(ltrim($className, '\\'));
	}

	public static function getFunctionDeclarationKey(string $functionName): string
	{
		return 'function ' . strtolower(ltrim($functionName, '\\'));
	}

	/**
	 * What a file declares besides its classes and functions, and what its anonymous classes do - a
	 * file depending on any symbol of the file depends on it.
	 *
	 * @param string $file normalized, see getFileKey()
	 */
	public static function getFileDeclarationKey(string $file): string
	{
		return 'file ' . $file;
	}

	/**
	 * The keys of every declaration resolved in $file so far - the analysed file depends on all of
	 * them, they can be declared in a function body, or by a class declared elsewhere using a trait
	 * of the file.
	 *
	 * @return list<string>
	 */
	public function getDeclarationKeysOfFile(string $file): array
	{
		return array_keys($this->fileDeclarationKeys[$file] ?? []);
	}

	/**
	 * Until leaveDeclaration(), what is looked up is recorded for the declaration of $className -
	 * or of $functionName outside a class, or of $file outside both - too: while resolving something
	 * its reflection remembers for the rest of the process, its ancestors, the types in its PHPDocs,
	 * the initializers of its constants. Files depending on the declaration depend on what is found
	 * for it, see getFoundSymbols(), and files depending on $file on what is missing.
	 *
	 * @param string|null $className the class declaring it - the class using the trait for a trait member
	 */
	public function enterDeclaration(?string $file, ?string $className = null, ?string $functionName = null): void
	{
		$declarationKey = null;
		if ($file !== null) {
			$file = $this->getFileKey($file);
			if ($className !== null) {
				// the name of an anonymous class is not the same everywhere
				$declarationKey = str_starts_with($className, 'AnonymousClass')
					? self::getFileDeclarationKey($file)
					: self::getClassDeclarationKey($className);
			} elseif ($functionName !== null) {
				$declarationKey = self::getFunctionDeclarationKey($functionName);
			} else {
				$declarationKey = self::getFileDeclarationKey($file);
			}
			$this->fileDeclarationKeys[$file][$declarationKey] = true;
		}

		$declaration = [$file, $declarationKey];
		if ($this->declarations === [] || $this->declarations[count($this->declarations) - 1] !== $declaration) {
			self::$context++;
		}
		$this->declarations[] = $declaration;
	}

	public function leaveDeclaration(): void
	{
		$declaration = array_pop($this->declarations);
		if ($this->declarations !== [] && $this->declarations[count($this->declarations) - 1] === $declaration) {
			return;
		}

		self::$context++;
	}

	/**
	 * @param self::KIND_* $kind
	 * @return array{class-string<ResultCacheValueExtension>, string}
	 */
	private static function getExtensionAndKey(string $kind, string $name): array
	{
		switch ($kind) {
			case self::KIND_CLASS:
				return [ClassResultCacheValueExtension::class, ClassResultCacheValueExtension::createKey($name)];
			case self::KIND_FUNCTION:
				return [FunctionResultCacheValueExtension::class, FunctionResultCacheValueExtension::createKey($name)];
			default:
				return [ConstantResultCacheValueExtension::class, ConstantResultCacheValueExtension::createKey($name)];
		}
	}

	/**
	 * @param self::KIND_* $kind
	 */
	private static function isValidSymbolName(string $kind, string $name): bool
	{
		if (!ClassNameHelper::isValidClassName($name)) {
			return false;
		}

		if ($kind !== self::KIND_CONSTANT) {
			return true;
		}

		// resolved by the parser, never declared
		$lastBackslash = strrpos($name, '\\');
		$shortName = strtolower($lastBackslash === false ? $name : substr($name, $lastBackslash + 1));

		return !in_array($shortName, ['true', 'false', 'null'], true);
	}

	/**
	 * The analysed file depends on the class - it gets what the class and its ancestors declare
	 * depends on, see recordForClass().
	 */
	public function noteClassDependency(ClassReflection $classReflection): void
	{
		if ($this->analysedFile === null) {
			return;
		}

		$this->dependedOnClasses[strtolower($classReflection->getName())] = true;
		foreach (array_keys($classReflection->getAncestors()) as $ancestorName) {
			$this->dependedOnClasses[strtolower($ancestorName)] = true;
		}
	}

	/**
	 * @return ValueDependencies always with an entry for the analysed file
	 */
	public function finishFile(): array
	{
		if ($this->analysedFile !== null) {
			foreach (array_keys($this->dependedOnClasses) as $className) {
				foreach ($this->classDeclarations[$className] ?? [] as $id => $value) {
					$this->values[$id] ??= $value;
					$this->dependents[$this->analysedFile]['analysis'][$id] = true;
				}
			}
		}

		$dependents = [];
		foreach ($this->dependents as $dependentFile => ['analysis' => $analysis, 'declarations' => $declarations]) {
			$dependents[$dependentFile] = [
				'analysis' => array_keys($analysis),
				'declarations' => array_keys($declarations),
			];
		}

		$values = $this->values;
		$this->analysedFile = null;
		$this->values = [];
		$this->dependents = [];
		$this->dependedOnClasses = [];
		$this->analysisFoundSymbols = [];
		self::$context++;

		return ['values' => $values, 'dependents' => $dependents];
	}

	/**
	 * The dependencies recorded during the analysis of one file can be about another file too,
	 * so the results of several files are merged, not overwritten. A value seen first stays.
	 *
	 * @param ValueDependencies $dependencies
	 * @param ValueDependencies $newDependencies
	 * @return ValueDependencies
	 */
	public static function merge(array $dependencies, array $newDependencies): array
	{
		$values = $dependencies['values'] + $newDependencies['values'];
		$dependents = $dependencies['dependents'];
		foreach ($newDependencies['dependents'] as $dependentFile => ['analysis' => $analysis, 'declarations' => $declarations]) {
			$dependents[$dependentFile] = [
				'analysis' => array_values(array_unique(array_merge($dependents[$dependentFile]['analysis'] ?? [], $analysis))),
				'declarations' => array_values(array_unique(array_merge($dependents[$dependentFile]['declarations'] ?? [], $declarations))),
			];
		}

		return ['values' => $values, 'dependents' => $dependents];
	}

	public function getExtension(string $extensionClass): ?ResultCacheValueExtension
	{
		foreach ($this->valueExtensions->getAll() as $extension) {
			if ($extension instanceof $extensionClass) {
				return $extension;
			}
		}

		return null;
	}

	private function getRegisteredExtension(string $extensionClass): ResultCacheValueExtension
	{
		$extension = $this->getExtension($extensionClass);
		if ($extension === null) {
			throw new ShouldNotHappenException(sprintf(
				'%s is not registered as a result cache value extension. Register it with the %s service tag.',
				$extensionClass,
				ResultCacheValueExtension::EXTENSION_TAG,
			));
		}

		return $extension;
	}

}
