<?php declare(strict_types = 1);

namespace Bug10367;

final class PackingSlipPdf
{
	public function __construct(array $packingSlipItems, string $fullName)
	{
		if (empty($packingSlipItems)) {
			error_log('$packingSlipItems is empty: check company: ' . $fullName);
		} else {
			$freightNumbers = array_column($packingSlipItems, 3);
			if (empty($freightNumbers)) {
				error_log('$freightNumbers is empty: check company: ' . $fullName);
				error_log('$freightNumbers is empty: check dataset: ' . print_r($packingSlipItems[0], true));
			} elseif (empty($freightNumbers[0])) {
				error_log('$freightNumbers[0] is empty: check company: ' . $fullName);
				error_log('$freightNumbers[0] is empty: check freigthnumbers: ' . print_r($freightNumbers, true));
				error_log('$freightNumbers[0] is empty: check dataset: ' . print_r($packingSlipItems[0], true));
			}
		}
	}
}

$testArray = [
	[1 => 123, 2 => 413, 4 => 132],
	[1 => 123, 2 => 413, 4 => 132],
];

$packingSlipPdf = new PackingSlipPdf($testArray, "TestName");
