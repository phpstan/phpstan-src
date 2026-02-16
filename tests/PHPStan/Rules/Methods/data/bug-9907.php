<?php declare(strict_types = 1);

namespace Bug9907Rule;

class Demo
{
	/**
	 * @phpstan-param array{street: string, city: string} $address1
	 * @phpstan-param array{street: string, city: string} $address2
	 *
	 * @phpstan-return array{
	 *     street?: array{change_to: string},
	 *     city?: array{change_to: string},
	 *     variation_count?: int<1, max>
	 * }
	 */
	public function diffAddresses(array $address1, array $address2): array
	{
		$addressDifference = array_diff_assoc($address1, $address2);
		$differenceDetails = [];

		foreach ($addressDifference as $name => $differenceValue) {
			$differenceDetails[$name] = [
				'change_to' => $differenceValue,
			];
		}

		if (!empty(count($differenceDetails))) {
			$differenceDetails['variation_count'] = count($differenceDetails);
		}

		return $differenceDetails;
	}
}
