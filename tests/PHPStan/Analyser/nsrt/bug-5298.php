<?php

declare(strict_types = 1);

namespace Bug5298;

use function PHPStan\Testing\assertType;

interface WorldProvider{}
interface WritableWorldProvider extends WorldProvider{}

/**
 * @phpstan-template TWorldProvider of WorldProvider
 */
class WorldProviderManagerEntry{
	/** @phpstan-param TWorldProvider $provider */
	public function acceptsTWorldProvider(WorldProvider $provider) : void{}
}

final class WorldProviderManager{
	/** @phpstan-var array<string, WorldProviderManagerEntry<WorldProvider>> */
	protected $providers = [];

	/**
	 * @phpstan-template T of WorldProvider
	 * @phpstan-param WorldProviderManagerEntry<T> $providerEntry
	 */
	public function addProvider(WorldProviderManagerEntry $providerEntry, string $name, bool $overwrite = false) : void{
		$name = strtolower($name);
		if(!$overwrite and isset($this->providers[$name])){
			throw new \InvalidArgumentException("Alias \"$name\" is already assigned");
		}
		assertType('array<string, Bug5298\WorldProviderManagerEntry<Bug5298\WorldProvider>>', $this->providers);
		assertType('Bug5298\WorldProviderManagerEntry<T of Bug5298\WorldProvider (method Bug5298\WorldProviderManager::addProvider(), argument)>', $providerEntry);
		$this->providers[$name] = $providerEntry;
	}

	public function doSomething(string $name, WorldProvider $provider) : void{
		assertType('Bug5298\WorldProviderManagerEntry<Bug5298\WorldProvider>', $this->providers[$name]);
		$this->providers[$name]->acceptsTWorldProvider($provider);
	}
}
