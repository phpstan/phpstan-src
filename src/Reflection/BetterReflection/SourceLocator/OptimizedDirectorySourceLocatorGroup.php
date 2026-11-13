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
use function strtolower;

/**
 * Asks OptimizedDirectorySourceLocators as if they were asked in turn, the way
 * AggregateSourceLocator would: the first one locating the symbol answers. Only the ones whose
 * symbol map has the name are asked, and the maps are checked directly instead of through each
 * locator - a project with many Composer packages has a locator for each classmap directory, and
 * most names asked for are declared in none of them. The maps are the locators' own: one map of
 * all their symbols would answer in one lookup, but it would keep a second copy of every name for
 * the rest of the process.
 */
final class OptimizedDirectorySourceLocatorGroup implements SourceLocator
{

	/** @var array{list<array<string, mixed>>, list<array<string, mixed>>, list<array<string, mixed>>}|null the class, function and constant maps of each locator, by its index */
	private ?array $symbolMaps = null;

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

		$this->symbolMaps ??= $this->collectSymbolMaps();
		foreach ($this->symbolMaps[$kind] as $index => $symbols) {
			if (!isset($symbols[$name])) {
				continue;
			}

			$reflection = $this->locators[$index]->locateIdentifier($reflector, $identifier);
			if ($reflection !== null) {
				return $reflection;
			}
		}

		return null;
	}

	/**
	 * @return array{list<array<string, mixed>>, list<array<string, mixed>>, list<array<string, mixed>>}
	 */
	private function collectSymbolMaps(): array
	{
		$symbolMaps = [[], [], []];
		foreach ($this->locators as $locator) {
			[$classes, $functions, $constants] = $locator->getSymbolMaps();
			$symbolMaps[0][] = $classes;
			$symbolMaps[1][] = $functions;
			$symbolMaps[2][] = $constants;
		}

		return $symbolMaps;
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
