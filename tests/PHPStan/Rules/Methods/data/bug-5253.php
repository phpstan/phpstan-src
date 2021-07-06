<?php

namespace Bug5253;

interface HttpClientPoolItem
{
	public function isDisabled(): bool;
}

final class RoundRobinClientPool
{
	/**
	 * @var HttpClientPoolItem[]
	 */
	protected $clientPool = [];

	protected function chooseHttpClient(): HttpClientPoolItem
	{
		$last = current($this->clientPool);

		do {
			$client = next($this->clientPool);

			if (false === $client) {
				$client = reset($this->clientPool);

				if (false === $client) {
					throw new \Exception();
				}
			}

			// Case when there is only one and the last one has been disabled
			if ($last === $client && $client->isDisabled()) {
				throw new \Exception();
			}
		} while ($client->isDisabled());

		return $client;
	}
}
