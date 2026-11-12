<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\ConstantNameHelper;
use function array_merge;
use function count;
use function is_int;
use function strtolower;

/**
 * Asks OptimizedDirectorySourceLocators as if they were asked in turn, the way
 * AggregateSourceLocator would: the first one locating the symbol answers. Which of them declare
 * it is looked up once, in a map of all their symbols built when the group is first asked, instead
 * of in every one of them - a project with many Composer packages has a locator for each classmap
 * directory, and most names asked for are declared in none of them.
 */
final class OptimizedDirectorySourceLocatorGroup implements SourceLocator
{

	/** @var array{array<string, int|list<int>>, array<string, int|list<int>>, array<string, int|list<int>>}|null class, function and constant name => the locators declaring it */
	private ?array $declaringLocators = null;

	/**
	 * @param list<OptimizedDirectorySourceLocator> $locators
	 */
	public function __construct(private array $locators)
	{
	}

	/**
	 * @param list<OptimizedDirectorySourceLocator> $locators
	 */
	public static function create(array $locators): SourceLocator
	{
		if (count($locators) === 1) {
			return $locators[0];
		}

		return new self($locators);
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$kind = 0;
			$name = strtolower($identifier->getName());
		} elseif ($identifier->isFunction()) {
			$kind = 1;
			$name = strtolower($identifier->getName());
		} elseif ($identifier->isConstant()) {
			$kind = 2;
			$name = ConstantNameHelper::normalize($identifier->getName());
		} else {
			return null;
		}

		$this->declaringLocators ??= $this->buildDeclaringLocators();
		$indexes = $this->declaringLocators[$kind][$name] ?? null;
		if ($indexes === null) {
			return null;
		}

		foreach (is_int($indexes) ? [$indexes] : $indexes as $index) {
			$reflection = $this->locators[$index]->locateIdentifier($reflector, $identifier);
			if ($reflection !== null) {
				return $reflection;
			}
		}

		return null;
	}

	/**
	 * @return array{array<string, int|list<int>>, array<string, int|list<int>>, array<string, int|list<int>>}
	 */
	private function buildDeclaringLocators(): array
	{
		$declaringLocators = [[], [], []];
		foreach ($this->locators as $index => $locator) {
			foreach ($locator->getDeclaredSymbolNames() as $kind => $names) {
				foreach ($names as $name) {
					$declaring = $declaringLocators[$kind][$name] ?? null;
					if ($declaring === null) {
						$declaringLocators[$kind][$name] = $index;
						continue;
					}

					$declaring = is_int($declaring) ? [$declaring] : $declaring;
					$declaring[] = $index;
					$declaringLocators[$kind][$name] = $declaring;
				}
			}
		}

		return $declaringLocators;
	}

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		$reflections = [];
		foreach ($this->locators as $locator) {
			$reflections[] = $locator->locateIdentifiersByType($reflector, $identifierType);
		}

		return array_merge([], ...$reflections);
	}

}
