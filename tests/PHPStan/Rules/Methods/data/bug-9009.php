<?php declare(strict_types=1);

namespace Bug9009;

class Hook
{
	/**
	 * @param \Closure($this): object $fx
	 */
	protected function addHook(?\Closure $fx): int
	{
		return 1;
	}

	/**
	 * @param \Closure($this): object $fx
	 */
	public function addHookDynamic(\Closure $fx): int
	{
		return $this->addHook($fx);
	}
}

(new Hook())->addHookDynamic(function (Hook $hook) {
	return new \stdClass();
});
