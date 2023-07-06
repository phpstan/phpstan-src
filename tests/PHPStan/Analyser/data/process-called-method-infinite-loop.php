<?php declare(strict_types = 1);

namespace ProcessCalledMethodInfiniteLoop;

/**
 * @template TValue
 */
class Promise{
	/** @var TValue|null */
	private $value = null;

	/** @param \Closure(TValue|null) : void $callback */
	public function onResolve(\Closure $callback) : void{
		$callback($this->value);
	}
}
class HelloWorld
{
	/**
	 * @template TValue
	 * @param \Generator<int, Promise<TValue|null>, TValue|null, void> $async
	 */
	public function next(\Generator $async) : void{
		$async->next();
		if(!$async->valid()) return;
		$promise = $async->current();
		$promise->onResolve(function($value) use ($async) : void{
			$async->send($value);
			$this->next($async);
		});
	}
}
