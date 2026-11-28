<?php declare(strict_types = 1);

namespace Bug15869;

use function PHPStan\Testing\assertType;

/**
 * @param mixed $messages
 * @param mixed $lines
 * @param mixed $parts
 * @param mixed $t
 */
function parse(string $name, $messages, $lines, $parts, $t): void
{
	$data['header'][0]['name'] = $name;
	for ($m = 0; $m < count($messages); $m++) {
		if (count($lines) > 1) {
			$data['message'][$m]['header']['_pointer'] = 'header[0][id]';
			for ($l = 0; $l < count($lines); $l++) {
				if (preg_match('/^((:20:)|(:28:)|(:60:)|(:65:)|(:86:))(.*)$/', $lines[$l], $matches) !== 0) {
					switch ($matches[1]) {
						case ':20:':
							$data['message'][$m]['reference'] = trim(substr($lines[$l], 4, 16));
							$data['message'][$m]['account'] = trim(substr($lines[$l], 4, 35));
							break;
						case ':28:':
							$data['message'][$m]['number'] = trim($parts[0]);
							$data['message'][$m]['sequence'] = trim($parts[1] ?? '');
							break;
						case ':60:':
							if (!isset($data['message'][$m]['startSign'])) {
								$data['message'][$m]['startSign'] = trim(substr($lines[$l], 5, 1));
								$data['message'][$m]['startDate'] = trim(substr($lines[$l], 6, 2));
								$data['message'][$m]['startCurrency'] = trim(substr($lines[$l], 12, 3));
								$data['message'][$m]['startBalance'] = trim(substr($lines[$l], 15, 15));
							}
							$data['transaction'][$t]['description'] .= trim($lines[$l]);
							break;
						case ':65:':
							$data['message'][$m]['endCurrency'] = trim(substr($lines[$l], 11, 3));
							$data['message'][$m]['endBalance'] = trim(substr($lines[$l], 14, 15));
							break;
					}
				}
			}
			assertType("array{header: array{array{name: string}}, message: non-empty-array<int<0, max>, array{reference?: string, account?: string, number?: string, sequence?: string, endCurrency?: string, endBalance?: string, header?: array{_pointer: 'header[0][id]'}, startSign?: string, startDate?: string, startCurrency?: string, startBalance?: string}>, transaction?: non-empty-array<array{description: string}>}", $data);
			foreach ($data['transaction'] as $transaction) {
			}
		}
	}
}
