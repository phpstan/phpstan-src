<?php declare(strict_types = 1);

namespace Bug5298;

interface WorldProvider{}

interface WritableWorldProvider extends WorldProvider{}

/**
 * @phpstan-template TWorldProvider of WorldProvider
 * @phpstan-type IsValid \Closure(string $path) : bool
 * @phpstan-type FromPath \Closure(string $path) : TWorldProvider
 */
class WorldProviderManagerEntry{
	/** @phpstan-param TWorldProvider $provider */
	public function acceptsTWorldProvider(WorldProvider $provider) : void{}
}


final class WorldProviderManager{
	/**
	 * @var WorldProviderManagerEntry[]
	 * @phpstan-var array<string, WorldProviderManagerEntry<WorldProvider>>
	 */
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

		$this->providers[$name] = $providerEntry; //should be an error, T of WorldProvider is not invariant with WorldProvider
		\PHPStan\dumpType($providerEntry);
		\PHPStan\dumpType($this->providers);
	}

	public function doSomething(string $name, WorldProvider $provider) : void{
		$this->providers[$name]->acceptsTWorldProvider($provider); //error, WorldProvider might not be a subclass of the template bound
	}
}

$p = new WorldProviderManager();
/** @phpstan-var WorldProviderManagerEntry<WritableWorldProvider> */
$entry = new WorldProviderManagerEntry(); //acceptsTWorldProvider() doesn't accept WorldProvider
$p->addProvider($entry, "test");
$p->doSomething("test", new class implements WorldProvider{}); //bang
