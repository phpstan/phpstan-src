<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use ReflectionFunction;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use function class_exists;
use function function_exists;
use function get_included_files;
use function in_array;
use function interface_exists;
use function PHPStan\autoloadFunctions;
use function PHPStan\autoloadFunctionsPrependedToComposer;
use function restore_error_handler;
use function set_error_handler;
use function trait_exists;

final class AutoloadFunctionsSourceLocator implements SourceLocator
{

	/**
	 * @param bool $prependedToComposer When true, consult only the autoloaders
	 *   registered before Composer's class loader (prepended); otherwise consult
	 *   the ones registered after it (appended).
	 */
	public function __construct(
		private AutoloadSourceLocator $autoloadSourceLocator,
		private ReflectionClassSourceLocator $reflectionClassSourceLocator,
		private bool $prependedToComposer,
	)
	{
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if (!$identifier->isClass()) {
			return null;
		}

		$className = $identifier->getName();
		if (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false)) {
			return null;
		}

		$autoloadFunctions = $this->prependedToComposer
			? autoloadFunctionsPrependedToComposer()
			: autoloadFunctions();

		if ($autoloadFunctions === []) {
			return null;
		}

		if (function_exists($className)) {
			if ($this->wouldReIncludeALoadedFile($autoloadFunctions, $className)) {
				return null;
			}

			// The trap intercepts file reads, not execution, so the probe ran the autoloaders for
			// real. One that defines the class without reading a file - class_alias(), eval() -
			// has already done its work, and calling it again would redeclare what it defined.
			if (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false)) {
				return $this->locateWithoutAutoloading($reflector, $identifier);
			}
		}

		foreach ($autoloadFunctions as $autoloadFunction) {
			$autoloadFunction($className);

			$reflection = $this->locateWithoutAutoloading($reflector, $identifier);
			if ($reflection !== null) {
				return $reflection;
			}
		}

		return null;
	}

	private function locateWithoutAutoloading(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		$reflection = $this->autoloadSourceLocator->locateIdentifier($reflector, $identifier);
		if ($reflection !== null) {
			return $reflection;
		}

		return $this->reflectionClassSourceLocator->locateIdentifier($reflector, $identifier);
	}

	/**
	 * Whether running these autoloaders for $className would include a file that is loaded already.
	 *
	 * A function of this name exists, so an autoloader that maps names to paths - a catch-all one
	 * like PHP_CodeSniffer's, falling back to Composer's findFile() - can resolve this *class* name
	 * to the *function's* own file. Including that file a second time fatally redeclares the
	 * function, which is what https://github.com/phpstan/phpstan/issues/14988 reported.
	 *
	 * Probing under the file-read trap answers which file the autoloaders would read without
	 * executing it, so only that case is declined. Declining on the name alone would also block
	 * class names that merely coincide with a function - classes and functions live in separate
	 * symbol spaces, and Laravel's facade aliases (Cache, File, Str, ...) collide with the global
	 * helpers cache(), file() and str(). See https://github.com/phpstan/phpstan/issues/15102
	 *
	 * @param array<int, callable(string): void> $autoloadFunctions
	 */
	private function wouldReIncludeALoadedFile(array $autoloadFunctions, string $className): bool
	{
		set_error_handler(static fn (): bool => true);

		try {
			$locatedFiles = FileReadTrapStreamWrapper::withStreamWrapperOverride(
				static function () use ($autoloadFunctions, $className): array {
					foreach ($autoloadFunctions as $autoloadFunction) {
						$autoloadFunction($className);

						// Stop as soon as the name is defined, the way spl_autoload_call() does:
						// a later autoloader must not get the chance to resolve a name that is
						// already taken care of. Under the trap a file read cannot define
						// anything, so this means the autoloader defined it by itself.
						if (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false)) {
							return [];
						}

						if (FileReadTrapStreamWrapper::$autoloadLocatedFiles !== []) {
							return FileReadTrapStreamWrapper::$autoloadLocatedFiles;
						}
					}

					return [];
				},
			);
		} finally {
			restore_error_handler();
		}

		if ($locatedFiles === []) {
			return false;
		}

		// Only re-including the file that *declares the function of this name* can redeclare it.
		// A trapped read of any other file is not the hazard: an aliasing autoloader reads the
		// file of the class it aliases to, and that file already being loaded is the normal case -
		// class_alias() includes nothing, it names a class that is there. A built-in function has
		// no declaring file, so there is nothing it could redeclare.
		$functionFileName = (new ReflectionFunction($className))->getFileName();
		if ($functionFileName === false) {
			return false;
		}

		// PHP canonicalises the path before it reaches a stream wrapper - a `/./` segment, a
		// symlinked directory or an include-path-relative name all arrive resolved - so the
		// trapped paths compare directly against get_included_files().
		$includedFiles = get_included_files();
		foreach ($locatedFiles as $locatedFile) {
			if ($locatedFile !== $functionFileName) {
				continue;
			}

			if (in_array($locatedFile, $includedFiles, true)) {
				return true;
			}
		}

		return false;
	}

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
