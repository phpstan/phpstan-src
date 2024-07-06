<?php declare(strict_types=1);

namespace Bug11283;

/**
 * @return mixed
 */
function returnMixed()
{
	return 1;
}

/**
 * Partial copy of https://github.com/reactphp/promise/blob/3.x/src/PromiseInterface.php
 * @template-covariant T
 */
interface PromiseInterface
{
	/**
	 * @template TFulfilled
	 * @template TRejected
	 * @param ?(callable((T is void ? null : T)): (PromiseInterface<TFulfilled>|TFulfilled)) $onFulfilled
	 * @param ?(callable(\Throwable): (PromiseInterface<TRejected>|TRejected)) $onRejected
	 * @return PromiseInterface<($onRejected is null ? ($onFulfilled is null ? T : TFulfilled) : ($onFulfilled is null ? T|TRejected : TFulfilled|TRejected))>
	 */
	public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface;

	/**
	 * @template TThrowable of \Throwable
	 * @template TRejected
	 * @param callable(TThrowable): (PromiseInterface<TRejected>|TRejected) $onRejected
	 * @return PromiseInterface<T|TRejected>
	 */
	public function catch(callable $onRejected): PromiseInterface;

	/**
	 * @param callable(): (void|PromiseInterface<void>) $onFulfilledOrRejected
	 * @return PromiseInterface<T>
	 */
	public function finally(callable $onFulfilledOrRejected): PromiseInterface;
}

/**
 * @template T
 * @param PromiseInterface<T>|T $promiseOrValue
 * @return PromiseInterface<T>
 */
function resolve($promiseOrValue): PromiseInterface
{
	return returnMixed();
}

class Demonstration
{
	public function parseMessage(): void
	{
		$params = [];
		$packet = [];
		$promise = resolve(null);

		$promise->then(function () use (&$packet, &$params) {
			if (mt_rand(0, 1)) {
				resolve(null)->then(
					function () use ($packet, &$params) {
						$packet['payload']['type'] = 0;
						$this->groupNotify(
							$packet,
							function () use ($packet, &$params) {
								$this->save($packet, $params)->then(function () use ($packet, &$params) {
									if ($params['links']) {
										$this->handle($params)->then(function ($result) use ($packet) {
											if ($result) {
												$packet['payload']['preview'] = $result;
											}
										});
									}
								});
							}
						);
					}
				);
			} else {
				$this->call(function () use (&$params) {
					$packet['target'] = [];

					$this->asyncAction()->then(function ($value) use (&$packet, &$params) {
						if (!$value) {
							$packet['payload']['type'] = 0;
							$packet['payload']['message'] = '';
							$this->selfNotify($packet);
							return;
						}

						$packet['payload']['type'] = 0;
						$this->groupNotify(
							$packet,
							function () use ($packet, &$params) {
								$this->save($packet, $params)->then(function () use ($packet, &$params) {
									if ($params) {
										$this->handle($params)->then(function ($result) use ($packet) {
											if ($result) {
												$packet['payload']['preview'] = $result;
												$this->selfNotify($packet);
											}
										});
									}
								});
							}
						);
					});
				});
			}
		});
	}

	/**
	 * @return PromiseInterface<mixed>
	 */
	private function handle(mixed $params): PromiseInterface
	{
		return resolve(null);
	}

	/**
	 * @param array<string, mixed> $packet
	 * @param array<string, mixed> $params
	 * @return PromiseInterface<int>
	 */
	private function save(array $packet, array &$params): PromiseInterface
	{
		return resolve(0);
	}

	/**
	 * @param array<string, mixed> $packet
	 * @param (callable():void) $callback
	 * @return bool
	 */
	public function groupNotify(array $packet, callable $callback): bool
	{
		return true;
	}

	/**
	 * @param array<string, mixed> $packet
	 * @return bool
	 */
	public function selfNotify(array $packet): bool
	{
		return true;
	}

	/**
	 * @return PromiseInterface<mixed>
	 */
	private function asyncAction(): PromiseInterface
	{
		return resolve('');
	}

	/**
	 * @param callable():void $callback
	 */
	private function call(callable $callback): void
	{
	}
}

