<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function is_array;

#[AutowiredService]
final class Cache
{

	private bool $arenaUsable = false;

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
		// Every data-only entry is also shared across the run's parallel
		// workers through the shared-memory arena (turbo extension only; the
		// seam is a no-op otherwise): whichever process loads or saves an
		// entry first publishes it, and the others skip the include() of the
		// var_export'd cache file. Payloads carrying objects stay per-worker
		// — encoding them transiently double-buffers at exactly the moments
		// worker memory peaks (measured, not guessed).
		$arenaKey = null;
		if ($this->isArenaUsable()) {
			$arenaKey = 'fcs:' . $key . "\0" . $variableKey;
			$cached = ArenaCache::lookup($arenaKey);
			if (is_array($cached)) {
				return $cached;
			}
		}

		$data = $this->storage->load($key, $variableKey);
		if ($data !== null && $arenaKey !== null) {
			$this->publishToArena($arenaKey, $data);
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

		$this->publishToArena('fcs:' . $key . "\0" . $variableKey, $data);
	}

	/**
	 * Whether this process is attached to a run's arena — probed with a
	 * canary record because the answer is not directly observable through
	 * the seam: without the extension or without an arena the publish is a
	 * no-op and the canary never appears. Memoized once it turns true;
	 * re-probed (two near-free calls) while false, so the master flips to
	 * publishing after it creates the arena.
	 */
	private function isArenaUsable(): bool
	{
		if ($this->arenaUsable) {
			return true;
		}

		ArenaCache::publish('fcs-arena-canary', true);
		if (ArenaCache::hasRecord('fcs-arena-canary')) {
			$this->arenaUsable = true;
		}

		return $this->arenaUsable;
	}

	/**
	 * @param mixed $data
	 */
	private function publishToArena(string $arenaKey, $data): void
	{
		if (!is_array($data)) {
			return;
		}

		// the arena's native serializer silently rejects anything that is
		// not data-only (objects inside) — those entries stay per-worker
		ArenaCache::publish($arenaKey, $data);
	}

}
