<?php declare(strict_types = 1); // lint >= 8.2

namespace NullCoalesceReadonlyPropertyNullableReceiver;

readonly class PluginMapping
{

	public function __construct(public string $pluginName, public string $snippetName)
	{
	}

}

/**
 * @template TElement
 * @template TKey of array-key = array-key
 */
abstract class Collection
{

	/** @var array<TKey, TElement> */
	protected array $elements = [];

	/**
	 * @param TKey $key
	 * @return TElement|null
	 */
	public function get($key)
	{
		return $this->elements[$key] ?? null;
	}

}

/**
 * @extends Collection<PluginMapping>
 */
class PluginMappingCollection extends Collection
{

}

class TranslationConfig
{

	public function __construct(public readonly PluginMappingCollection $pluginMapping)
	{
	}

	public function getMappedPluginName(string $pluginName): string
	{
		return $this->pluginMapping->get($pluginName)->snippetName ?? $pluginName;
	}

	public function getMappedPluginNameNonNullReceiver(PluginMapping $mapping): string
	{
		return $mapping->snippetName ?? 'default';
	}

}
