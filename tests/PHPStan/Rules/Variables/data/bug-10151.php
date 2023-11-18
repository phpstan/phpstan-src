<?php

namespace Bug10151;

class Test
{
	/**
	 * @var array<int>
	 */
	protected array $cache = [];

	public function getCachedItemId (string $keyName): void
	{
		$result = $this->cache[$keyName] ??= ($newIndex = count($this->cache) + 1);

		// WRONG ERROR: Variable $newIndex in isset() always exists and is not nullable.
		if (isset($newIndex)) {
			$this->recordNewCacheItem($keyName);
		}
	}

	protected function recordNewCacheItem (string $keyName): void {
		// ...
	}
}
