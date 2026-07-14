<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class Cache
{

	private ?bool $arenaUsable = null;

	public function __construct(
		#[AutowiredParameter(ref: '@cacheStorage')]
		private CacheStorage $storage,
	)
	{
	}

	/**
	 * @param non-empty-string $key
	 *
	 * @return mixed|null
	 */
	public function load(string $key, string $variableKey)
	{
		// Every entry is also shared across the run's parallel workers
		// through the shared-memory arena (turbo extension only; the seam is
		// a no-op otherwise): whichever process loads or saves an entry first
		// publishes it, and the others skip the include() of the var_export'd
		// cache file. The arena's codec covers scalars, arrays and plain
		// value objects, and interns repeated strings on read like include()
		// does; payloads it cannot represent just stay per-worker.
		$arenaKey = null;
		if ($this->isArenaUsable()) {
			$arenaKey = 'fcs:' . $key . "\0" . $variableKey;
			$cached = ArenaCache::lookup($arenaKey);
			if ($cached !== null) {
				return $cached;
			}
		}

		$data = $this->storage->load($key, $variableKey);
		if ($data !== null && $arenaKey !== null) {
			ArenaCache::publish($arenaKey, $data);
		}

		return $data;
	}

	/**
	 * @param non-empty-string $key
	 *
	 * @param mixed $data
	 */
	public function save(string $key, string $variableKey, $data): void
	{
		$this->storage->save($key, $variableKey, $data);

		if (!$this->isArenaUsable()) {
			return;
		}

		ArenaCache::publish('fcs:' . $key . "\0" . $variableKey, $data);
	}

	/**
	 * Whether this process is attached to a run's arena — probed once with a
	 * canary record because the answer is not directly observable through
	 * the seam: without the extension or without an arena the publish is a
	 * no-op and the canary never appears. Workers attach at boot, before any
	 * cache traffic; a process that touches the cache before its arena
	 * exists just stays unshared for its lifetime.
	 */
	private function isArenaUsable(): bool
	{
		if ($this->arenaUsable === null) {
			ArenaCache::publish('fcs-arena-canary', true);
			$this->arenaUsable = ArenaCache::hasRecord('fcs-arena-canary');
		}

		return $this->arenaUsable;
	}

}
