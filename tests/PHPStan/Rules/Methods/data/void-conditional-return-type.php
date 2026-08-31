<?php declare(strict_types = 1);

namespace VoidConditionalReturnType;

class Wormhole
{

	/**
	 * @template TReturn of mixed
	 *
	 * @param  (callable(): TReturn)|null  $callback
	 * @return ($callback is null ? void : TReturn)
	 */
	public function seconds($callback = null)
	{
		return $this->handleCallback($callback);
	}

	/**
	 * @param callable|null $callback
	 * @return mixed
	 */
	protected function handleCallback($callback)
	{
		if ($callback) {
			return $callback();
		}
	}

}

function test(Wormhole $wormhole): void
{
	$x = $wormhole->seconds();
	$wormhole->seconds();
	$y = $wormhole->seconds(static fn () => 1);
	echo $wormhole->seconds();
}
