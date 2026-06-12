<?php declare(strict_types = 1);

namespace Bug5009;

use Closure;

class Test
{
	protected Closure $callback;

	/**
	 * @param Closure(): void $callback
	 */
	public function __construct(Closure $callback)
	{
		$this->callback = $callback->bindTo($this, $this) ?? $callback;
	}
}
