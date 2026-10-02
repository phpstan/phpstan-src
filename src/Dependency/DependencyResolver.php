<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\NamespaceUsesTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use function array_key_exists;
use function array_values;
use function get_class;
use function is_string;
use function spl_object_id;
use function str_starts_with;

#[AutowiredService]
final class DependencyResolver
{

	private const PROFILE_EXPORT = 1;

	private const PROFILE_NAME_SCOPE = 2;

	/**
	 * Node classes ExportedNodeResolver::resolve() reacts to. A class member is not among them: it is
	 * exported as part of the class declaring it, through exportClassStatement(), never on its own.
	 */
	private const EXPORT_NODE_TYPES = [
		Node\Stmt\Class_::class,
		Node\Stmt\Interface_::class,
		Node\Stmt\Enum_::class,
		Node\Stmt\Trait_::class,
		Node\Stmt\Function_::class,
		Node\Stmt\Const_::class,
		Node\Expr\FuncCall::class,
	];

	/** Node classes NamespaceUsesTracker::enterNode() reacts to */
	private const NAME_SCOPE_NODE_TYPES = [
		Node\Stmt\Namespace_::class,
		Node\Stmt\Use_::class,
		Node\Stmt\GroupUse::class,
	];

	/** @var array<string, array<int, ClassReflection>> for the whole process */
	private array $classDependencies = [];

	/** @var array<class-string, int> */
	private array $nodeProfiles = [];

	private NamespaceUsesTracker $nameScopeTracker;

	private ?string $nameScopeFile = null;

	public function __construct(
		private FileHelper $fileHelper,
		private ReflectionProvider $reflectionProvider,
		private ExportedNodeResolver $exportedNodeResolver,
		private PackageDependencyResolver $packageDependencyResolver,
	)
	{
		$this->nameScopeTracker = new NamespaceUsesTracker();
	}

	/**
	 * Whether resolveExportedNode() can do anything with a node of this class.
	 */
	public function canExportNode(Node $node): bool
	{
		return ($this->nodeProfiles[get_class($node)] ??= $this->resolveNodeProfile($node)) !== 0;
	}

	/**
	 * What the node declares, so that the result cache notices when what a file declares changes.
	 */
	public function resolveExportedNode(Node $node, Scope $scope): ?RootExportedNode
	{
		// The exported nodes written to the result cache have to record the same PHPDoc scope the
		// restore computes when it re-reads the file, so both go through the tracker. The nodes
		// arrive here in document order, one file at a time.
		$file = $scope->getFile();
		if ($file !== $this->nameScopeFile) {
			$this->nameScopeFile = $file;
			$this->nameScopeTracker->reset();
		}
		$nodeClass = get_class($node);
		$nodeProfile = $this->nodeProfiles[$nodeClass] ??= $this->resolveNodeProfile($node);

		if (($nodeProfile & self::PROFILE_NAME_SCOPE) !== 0) {
			$this->nameScopeTracker->enterNode($node);
		}

		// A function declared inside another function is not supported (function.inner), and the
		// restore does not look inside function bodies for exported nodes (ExportedNodeVisitor):
		// exporting it here would make every edit of its file look like a symbol disappeared.
		if (($nodeProfile & self::PROFILE_EXPORT) === 0 || ($node instanceof Node\Stmt\Function_ && $scope->getFunction() !== null)) {
			return null;
		}

		return $this->exportedNodeResolver->resolve($node, $this->nameScopeTracker->getNamespaceUses());
	}

	/**
	 * The files and the Composer packages declaring what the analysed file depends on - what the
	 * InternalStatementResult of its statements depends on.
	 *
	 * @param array<string, true> $analysedFiles
	 */
	public function resolveFileDependencies(?Dependencies $dependencies, array $analysedFiles): ResolvedDependencies
	{
		if ($dependencies === null) {
			return new ResolvedDependencies([], [], [], []);
		}

		// what each file depends on, in the order it was found: a class by its name, expanded to it and
		// its ancestors once below, however many times it was found, and a function or a constant
		/** @var array<string, array<string, string|FunctionReflection|ConstantReflection>> $foundByFile */
		$foundByFile = [];
		/** @var array<string, array<int, true>> $typesByFile */
		$typesByFile = [];
		/** @var array<string, array<int, ClassReflection>> $usedTraitsByFile */
		$usedTraitsByFile = [];
		/** @var array<string, string> $fileDependencies */
		$fileDependencies = [];
		$dependencies->walk(static function (string $file, array $types, array $classNames, array $reflections, array $filePaths, array $usedTraits) use (&$foundByFile, &$typesByFile, &$usedTraitsByFile, &$fileDependencies): void {
			foreach ($types as $type) {
				$typeId = spl_object_id($type);
				if (isset($typesByFile[$file][$typeId])) {
					continue;
				}

				$typesByFile[$file][$typeId] = true;
				foreach ($type->getReferencedClasses() as $className) {
					$foundByFile[$file]['c' . $className] ??= $className;
				}
			}
			foreach ($classNames as $className) {
				$foundByFile[$file]['c' . $className] ??= $className;
			}
			foreach ($reflections as $reflection) {
				$foundByFile[$file]['r' . spl_object_id($reflection)] = $reflection;
			}
			foreach ($filePaths as $filePath) {
				$fileDependencies[$filePath] = $filePath;
			}
			foreach ($usedTraits as $usedTrait) {
				$usedTraitsByFile[$file][spl_object_id($usedTrait)] = $usedTrait;
			}
		});

		/** @var array<string, array<int, ClassReflection|FunctionReflection|ConstantReflection>> $reflectionsByFile */
		$reflectionsByFile = [];
		foreach ($foundByFile as $file => $found) {
			$fileReflections = [];
			foreach ($found as $item) {
				if (is_string($item)) {
					$fileReflections += $this->getClassDependencies($item);
					continue;
				}

				$fileReflections[spl_object_id($item)] = $item;
			}
			$reflectionsByFile[$file] = $fileReflections;
		}

		$packages = [];
		$classReflections = [];
		foreach ($reflectionsByFile as $file => $reflections) {
			foreach ($reflections as $id => $reflection) {
				if (!$reflection instanceof ClassReflection) {
					continue;
				}

				$classReflections[$id] = $reflection;
			}
			$this->resolveFiles($file, $reflections, $analysedFiles, $fileDependencies, $packages);
		}

		$usedTraitFileDependencies = [];
		foreach ($usedTraitsByFile as $file => $usedTraits) {
			$this->resolveFiles($file, $usedTraits, $analysedFiles, $usedTraitFileDependencies, $packages);
		}

		return new ResolvedDependencies(
			array_values($fileDependencies),
			array_values($usedTraitFileDependencies),
			array_values($packages),
			array_values($classReflections),
		);
	}

	/**
	 * @return array<int, ClassReflection>
	 */
	private function getClassDependencies(string $className): array
	{
		if (!array_key_exists($className, $this->classDependencies)) {
			$this->classDependencies[$className] = $this->buildClassDependencies($className);
		}

		return $this->classDependencies[$className];
	}

	/**
	 * Which parts of resolveExportedNode() a node of this class can reach. Depends only on the class,
	 * so it is computed once per class and reused for every node of it.
	 */
	private function resolveNodeProfile(Node $node): int
	{
		$profile = 0;
		$lists = [
			self::PROFILE_EXPORT => self::EXPORT_NODE_TYPES,
			self::PROFILE_NAME_SCOPE => self::NAME_SCOPE_NODE_TYPES,
		];
		foreach ($lists as $bit => $nodeTypes) {
			foreach ($nodeTypes as $nodeType) {
				if (!$node instanceof $nodeType) {
					continue;
				}

				$profile |= $bit;
				break;
			}
		}

		return $profile;
	}

	/**
	 * The class with the classes what it declares refers to, and the same for its parents.
	 *
	 * @return array<int, ClassReflection>
	 */
	private function buildClassDependencies(string $className): array
	{
		try {
			$classReflection = $this->reflectionProvider->getClass($className);
		} catch (ClassNotFoundException) {
			return [];
		}

		$dependencies = [];
		do {
			$dependencies[] = $classReflection;

			foreach ($classReflection->getInterfaces() as $interface) {
				$dependencies[] = $interface;
			}

			foreach ($classReflection->getTraits(true) as $trait) {
				$dependencies[] = $trait;
			}

			$referencedTypes = [];
			foreach ($classReflection->getResolvedMixinTypes() as $mixinType) {
				$referencedTypes[] = $mixinType;
			}
			foreach ($classReflection->getRequireExtendsTags() as $extendsTag) {
				$referencedTypes[] = $extendsTag->getType();
			}
			foreach ($classReflection->getSealedTags() as $sealedTag) {
				$referencedTypes[] = $sealedTag->getType();
			}
			foreach ($classReflection->getTemplateTags() as $templateTag) {
				$referencedTypes[] = $templateTag->getBound();
				$referencedTypes[] = $templateTag->getDefault();
			}
			foreach ($classReflection->getPropertyTags() as $propertyTag) {
				if ($propertyTag->isReadable()) {
					$referencedTypes[] = $propertyTag->getReadableType();
				}
				if (!$propertyTag->isWritable()) {
					continue;
				}

				$referencedTypes[] = $propertyTag->getWritableType();
			}
			foreach ($classReflection->getMethodTags() as $methodTag) {
				$referencedTypes[] = $methodTag->getReturnType();
				foreach ($methodTag->getParameters() as $parameter) {
					$referencedTypes[] = $parameter->getType();
					$referencedTypes[] = $parameter->getDefaultValue();
				}
			}
			foreach ($classReflection->getExtendsTags() as $extendsTag) {
				$referencedTypes[] = $extendsTag->getType();
			}
			foreach ($classReflection->getImplementsTags() as $implementsTag) {
				$referencedTypes[] = $implementsTag->getType();
			}

			foreach ($referencedTypes as $referencedType) {
				if ($referencedType === null) {
					continue;
				}
				foreach ($referencedType->getReferencedClasses() as $referencedClass) {
					if (!$this->reflectionProvider->hasClass($referencedClass)) {
						continue;
					}
					$dependencies[] = $this->reflectionProvider->getClass($referencedClass);
				}
			}

			$phpDoc = $classReflection->getResolvedPhpDoc();
			if ($phpDoc !== null) {
				foreach ($phpDoc->getTypeAliasImportTags() as $importTag) {
					// guarded like every other tag above: the class an alias is imported from can be gone,
					// and asking for it then throws out of the whole walk - the file would record no
					// dependency at all, not even the parent class it extends
					$importedFrom = $importTag->getImportedFrom();
					if (!$this->reflectionProvider->hasClass($importedFrom)) {
						continue;
					}
					$dependencies[] = $this->reflectionProvider->getClass($importedFrom);
				}
			}

			$classReflection = $classReflection->getParentClass();
		} while ($classReflection !== null);

		$uniqueDependencies = [];
		foreach ($dependencies as $dependency) {
			$uniqueDependencies[spl_object_id($dependency)] = $dependency;
		}

		return $uniqueDependencies;
	}

	/**
	 * The files and packages the reflections found on a scope of $scopeFile are declared in:
	 *
	 * - a file that is analysed itself, or another project file - listed in scanFiles or
	 *   scanDirectories, excluded from the analysis but living in an analysed directory, or simply
	 *   reached through the autoloader - is a file dependency, so that editing it re-analyses only
	 *   the files depending on it instead of invalidating the whole result cache.
	 * - a file of an installed Composer package is resolved to the package name, so that a
	 *   composer.lock change re-analyses only the files depending on a package whose version
	 *   changed. A package installed from a path repository is both: it is the project's own code,
	 *   edited without Composer noticing.
	 *
	 * Files inside a PHAR belong to the running PHPStan itself and cannot change without its
	 * version changing, so they are left out.
	 *
	 * Built-in symbols of an extension whose stubs differ between its major versions are recorded
	 * as a package too, under the extension's platform package name (ext-<name>), so that selecting
	 * a different version re-analyses only the files using the extension. Their file is the PhpStorm
	 * stub they were read from - inside the PHAR, or in PHPStan's own vendor directory - which does
	 * not change with the selected version.
	 *
	 * @param array<int, ClassReflection|FunctionReflection|ConstantReflection> $reflections
	 * @param array<string, true> $analysedFiles
	 * @param array<string, string> $files
	 * @param array<string, string> $packages
	 */
	private function resolveFiles(string $scopeFile, array $reflections, array $analysedFiles, array &$files, array &$packages): void
	{
		foreach ($reflections as $reflection) {
			$extensionPackage = $this->packageDependencyResolver->resolveVersionedExtensionPackage($reflection);
			if ($extensionPackage !== null) {
				$packages[$extensionPackage] = $extensionPackage;
			}

			$dependencyFile = $reflection->getFileName();
			if ($dependencyFile === null || $dependencyFile === $scopeFile) {
				continue;
			}

			$normalizedDependencyFile = $this->fileHelper->normalizePath($dependencyFile);
			if ($normalizedDependencyFile === $scopeFile) {
				continue;
			}

			if (isset($analysedFiles[$normalizedDependencyFile])) {
				$files[$normalizedDependencyFile] = $normalizedDependencyFile;
				continue;
			}

			if (str_starts_with($dependencyFile, 'phar://')) {
				continue;
			}

			$package = $this->packageDependencyResolver->resolvePackage($normalizedDependencyFile);
			if ($package !== null) {
				$packages[$package] = $package;
				if (!$this->packageDependencyResolver->isPathPackage($package)) {
					continue;
				}
			}

			$files[$normalizedDependencyFile] = $normalizedDependencyFile;
		}
	}

}
