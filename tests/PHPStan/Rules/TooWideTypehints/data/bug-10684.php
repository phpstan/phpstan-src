<?php

namespace Bug10684;

abstract class HookBreaker extends Exception
{
	/**
	 * @return mixed
	 */
	public function getReturnValue()
	{
		return 1;
	}
}

class Foo
{
	/** @var \Closure(): void */
	protected \Closure $hook;

	/**
	 * @return mixed
	 */
	public function hook(HookBreaker &$brokenBy = null)
	{
		$brokenBy = null;

		$return = [];
		if (mt_rand() === 0) {
			try {
				($this->hook)();
			} catch (HookBreaker $e) {
				$brokenBy = $e;

				return $e->getReturnValue();
			}
		}

		return $return;
	}
}
