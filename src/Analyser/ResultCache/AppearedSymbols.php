<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use function class_exists;
use function defined;
use function function_exists;
use function interface_exists;
use function ltrim;
use function strtolower;
use function trait_exists;

/**
 * Which classes, functions and constants may have been declared since the result cache was
 * written - a lookup that found nothing then is asked again only for those, see
 * ValueDependencyCollector::recordMissingSymbol(). Every other one is still missing.
 * ResultCacheManager::restore() creates it, and the restored ResultCache carries it to whatever
 * checks the values recorded in the result cache, like the reuse of the errors of a rule on
 * collected data.
 *
 * A name can only appear through:
 *
 * - an analysed or scanned file that is new or changed, or another file the analysed ones depend
 *   on that changed - the names they declare, scanned the way the directory locators scan them.
 *   The files of the project's Composer autoload entries (PSR-4, PSR-0, classmap and files) count
 *   as scanned, see ResultCacheManager::getScannedFiles()
 * - a Composer package or a PHP extension of another version, the PHP version, PHPStan, a stub or a
 *   bootstrap file - each changes the metadata, and anything is possible then
 * - a file a bootstrap file requires - what it declares exists at runtime, see isStillMissing()
 */
final class AppearedSymbols
{

	/**
	 * @param array<string, true>|null $names lowercase names that may have been declared, null when anything may have
	 */
	private function __construct(private ?array $names)
	{
	}

	/**
	 * Anything may have been declared - every missing symbol is looked up again.
	 */
	public static function anything(): self
	{
		return new self(null);
	}

	/**
	 * @param list<string> $names declared by the files that are new or changed
	 */
	public static function fromNames(array $names): self
	{
		$lowercaseNames = [];
		foreach ($names as $name) {
			$lowercaseNames[strtolower($name)] = true;
		}

		return new self($lowercaseNames);
	}

	/**
	 * Whether a value of the extension that was ValueDependencyCollector::MISSING is still that,
	 * known without looking the symbol up.
	 */
	public function isStillMissing(string $extensionClass, string $key): bool
	{
		if ($this->names === null) {
			return false;
		}

		$key = ltrim($key, '\\');
		if (isset($this->names[strtolower($key)])) {
			return false;
		}

		// a bootstrap file, or a file it requires, declares symbols no tracked file shows - they
		// exist at runtime, without autoloading anything
		if ($extensionClass === ClassResultCacheValueExtension::class) {
			return !class_exists($key, false) && !interface_exists($key, false) && !trait_exists($key, false);
		}
		if ($extensionClass === FunctionResultCacheValueExtension::class) {
			return !function_exists($key);
		}
		if ($extensionClass === ConstantResultCacheValueExtension::class) {
			return !defined($key);
		}

		return false;
	}

}
