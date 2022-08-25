<?php declare(strict_types=1);

namespace Bug6008;

/**
 * @phpstan-type Request array{url: string, options?: mixed[], copyTo?: ?string}
 * @phpstan-type Job array{id: int, status: int, request: Request, sync: bool, origin: string, resolve?: callable, reject?: callable, curl_id?: int, response?: stdClass, exception?: stdClass}
 */
class HelloWorld
{
	public function sayHello(): void
	{
		/** @var Job */
		$job = array();

		if (isset($job['curl_id'])) {
		}

		$resolver = function () use (&$job) {
			$job['resolve'] = 'strlen'; // static callable, works
		};

		if (isset($job['curl_id'])) {
		}

		$resolver = function ($resolve) use ($job) { // no use-by-reference, works
			$job['resolve'] = $resolve;
		};

		if (isset($job['curl_id'])) {
		}

		// use-by-ref + variable callable assignment to a random key
		// triggers $job['curl_id'] to become set somehow
		$resolver = function ($resolve, $reject) use (&$job) {
			$job['resolve'] = $resolve;
		};

		if (isset($job['curl_id'])) {
		}
	}
}
