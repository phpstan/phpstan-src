<?php declare(strict_types = 1);

namespace Bug12215;

abstract class Spinlock
{
	private float $expireTimeout;

	private ?float $acquireTs = null;

	private ?string $token = null;

	public function __construct(float $expireTimeout = \PHP_INT_MAX)
	{
		$this->expireTimeout = $expireTimeout;

		// acquire lock
		$this->acquireTs = microtime(true);
		$this->token = random_bytes(64);
	}

	protected function release(string $key): bool
	{
		try {
			return $this->releaseWithToken($key, $this->token);
		} finally {
			$this->token = null;

			$elapsedTime = microtime(true) - $this->acquireTs;
			if ($elapsedTime >= $this->expireTimeout) {
				throw new \Exception('Execution outside lock exception');
			}
		}
	}

	protected function release2(string $key): bool
	{
		try {
			return $this->releaseWithToken($key, $this->token);
		} finally {
			try {
				$elapsedTime = microtime(true) - $this->acquireTs;
				if ($elapsedTime >= $this->expireTimeout) {
					throw new \Exception('Execution outside lock exception');
				}
			} finally {
				$this->token = null;
			}
		}
	}

	abstract protected function releaseWithToken(string $key, string $token): bool;
}
