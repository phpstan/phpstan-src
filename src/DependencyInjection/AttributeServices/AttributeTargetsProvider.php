<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use olvlvl\ComposerAttributeCollector\Attributes;
use olvlvl\ComposerAttributeCollector\TargetClass;
use olvlvl\ComposerAttributeCollector\TargetMethodParameter;
use function array_key_exists;
use function array_values;
use function sprintf;
use function strtolower;

/**
 * Single view over PHPStan's own attribute targets collected at composer dump time
 * into vendor/attributes.php and the targets discovered at container compile time
 * in the directories listed in the `attributeServicesDirectories` section.
 *
 * Targets from vendor/attributes.php win over discovered targets of the same class
 * so that pointing the section at PHPStan's own code cannot re-register services.
 */
final class AttributeTargetsProvider
{

	public function __construct(private DiscoveredAttributeTargets $discoveredTargets)
	{
	}

	public static function create(): self
	{
		require_once __DIR__ . '/../../../vendor/attributes.php';

		return new self(AttributeServicesDiscoveryContext::getTargets());
	}

	/**
	 * @template T of object
	 * @param class-string<T> $attributeClass
	 * @return list<TargetClass<T>>
	 */
	public function findTargetClasses(string $attributeClass): array
	{
		$targets = array_values(Attributes::findTargetClasses($attributeClass));
		$knownClasses = [];
		foreach ($targets as $target) {
			$knownClasses[strtolower($target->name)] = true;
		}

		foreach ($this->discoveredTargets->targetClasses[$attributeClass] ?? [] as $target) {
			if (array_key_exists(strtolower($target->name), $knownClasses)) {
				continue;
			}

			$targets[] = $target;
		}

		/** @var list<TargetClass<T>> */
		return $targets;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $attributeClass
	 * @return list<TargetMethodParameter<T>>
	 */
	public function findTargetMethodParameters(string $attributeClass): array
	{
		$targets = array_values(Attributes::findTargetMethodParameters($attributeClass));
		$knownParameters = [];
		foreach ($targets as $target) {
			$knownParameters[self::getParameterKey($target)] = true;
		}

		foreach ($this->discoveredTargets->targetMethodParameters[$attributeClass] ?? [] as $target) {
			if (array_key_exists(self::getParameterKey($target), $knownParameters)) {
				continue;
			}

			$targets[] = $target;
		}

		/** @var list<TargetMethodParameter<T>> */
		return $targets;
	}

	/**
	 * @param TargetMethodParameter<object> $target
	 */
	private static function getParameterKey(TargetMethodParameter $target): string
	{
		return sprintf('%s::%s $%s', strtolower($target->class), strtolower($target->method), $target->name);
	}

}
