<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use function class_exists;
use function function_exists;
use function interface_exists;
use function opcache_invalidate;
use function PHPStan\autoloadFunctions;
use function PHPStan\autoloadFunctionsPrependedToComposer;
use function restore_error_handler;
use function set_error_handler;
use function trait_exists;

/**
 * Consults the autoload functions that bootstrap files registered - spl_autoload_register()
 * callbacks that are not Composer's class loader. Asked for a class, such an autoloader either
 * reads a file (which the file-read trap detects, so the class is located in it statically) or
 * defines the class without one, through class_alias() or eval().
 */
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

		$locatedFiles = $this->probeAutoloadFunctions($autoloadFunctions, $className);

		// An autoloader can define the class without reading any file - class_alias() with an
		// already-loaded target, or eval(). The trap intercepts file reads, not execution, so
		// such an autoloader has already done its work during the probe.
		if (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false)) {
			return $this->locateWithoutAutoloading($reflector, $identifier);
		}

		if ($locatedFiles === []) {
			return null;
		}

		// The autoloaders asked for these files - locate the class in them statically, without
		// executing anything.
		$reflection = $this->autoloadSourceLocator->locateIdentifierInFiles($reflector, $identifier, $locatedFiles);
		if ($reflection !== null) {
			return $reflection;
		}

		// The located files do not declare the class under this name. Running the autoloaders
		// for real can still resolve it: class_alias() whose target Composer has to autoload
		// first asks for the *target's* file, which never declares the alias name - Laravel's
		// Redirect alias reads the file of Illuminate\Support\Facades\Redirect. But a real run
		// is only safe when it cannot redeclare anything: a catch-all autoloader can resolve a
		// class name to the file of an already-loaded function of the same name and fatally
		// include it a second time, which is what
		// https://github.com/phpstan/phpstan/issues/14988 reported.
		if ($this->autoloadSourceLocator->wouldIncludingFilesRedeclareSymbols($locatedFiles)) {
			return null;
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
	 * Runs the autoload functions under the file-read trap and reports which files they asked
	 * for. No file content is executed - the trap serves empty data - so the probe is free of
	 * the side effects that make running bootstrap autoloaders for real hazardous. Mirrors
	 * spl_autoload_call() by stopping at the first autoloader that defines the name or asks
	 * for a file.
	 *
	 * @param array<int, callable(string): void> $autoloadFunctions
	 * @return string[]
	 */
	private function probeAutoloadFunctions(array $autoloadFunctions, string $className): array
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

		if (!function_exists('opcache_invalidate')) {
			return $locatedFiles;
		}

		// The pseudo-include may have cached the trap's empty content; running the autoloaders
		// for real afterwards has to compile the actual file.
		foreach ($locatedFiles as $locatedFile) {
			opcache_invalidate($locatedFile, true);
		}

		return $locatedFiles;
	}

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
