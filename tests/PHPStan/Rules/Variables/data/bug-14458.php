<?php declare(strict_types = 1);

namespace Bug14458;

use DateTime;

class Foo
{

	/**
	 * @param array<mixed> $payload
	 * @return array<mixed>
	 */
	public function doFoo(array $payload): array
	{
		if ($payload['a'] !== 'b') {
			throw new \Exception();
		}

		if (isset($payload['c'])) {
			$c = array_values(array_map(static fn (array $cd) => $cd[0], $payload['c']));
		}

		$convertedPriceWithVat = null;
		if (array_key_exists('cpwv', $payload)) {
			$convertedAmount = (float) $payload['cpwv']['awv'];

		}

		return [
			$payload['cf'],
			$payload['n'],
			$payload['d'] ?? null,
			$payload['mb'] ?? null,
			$payload['cr'],
			new DateTime($payload['p']),
			$payload['pn'],
			$payload['pd'] ?? null,
			$payload['piu'] ?? null,
			$convertedPriceWithVat,
			$payload['vi'],
			$payload['pi'],
			$payload['user']['name'] ?? null,
			$payload['user']['phone'] ?? null,
			$payload['ac'] ?? null,
			$c ?? null,
		];
	}

}
