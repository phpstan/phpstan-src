<?php declare(strict_types = 1);

namespace Bug15870;

use function PHPStan\Testing\assertType;

/**
 * @param list<list<string>> $messages
 * @param list<string> $parts
 */
function parse(string $name, array $messages, array $parts, string $line, int $t): void
{
	$data['header'][0]['name'] = $name;
	for ($m = 0; $m < count($messages); $m++) {
		$data['message'][$m]['header']['_pointer'] = 'header[0][id]';
		for ($l = 0; $l < count($messages[$m]); $l++) {
			switch ($messages[$m][$l]) {
				case 'a':
					$data['message'][$m]['reference'] = trim($line);
					$data['message'][$m]['account'] = trim($line);
				case 'b':
					$data['message'][$m]['number'] = trim($parts[0]);
					$data['message'][$m]['sequence'] = trim($parts[1] ?? '');
				case 'c':
					if (!isset($data['message'][$m]['startSign'])) {
						$data['message'][$m]['startSign'] = trim($line);
						$data['message'][$m]['startDate'] = trim($line);
						$data['message'][$m]['startCurrency'] = trim($line);
						$data['message'][$m]['startBalance'] = trim($line);
					}
					assertType("array{header: array{array{name: string}}, message: non-empty-array<int<0, max>, array{header?: array{_pointer: 'header[0][id]'}, endBalance?: string, startSign?: string, startDate?: string, startCurrency?: string, startBalance?: string, number?: string, sequence?: string, reference?: string, account?: string}>, transaction?: non-empty-array<int, array{description: string}>}", $data);
					$data['transaction'][$t]['description'] .= trim($line);
				case 'd':
					$data['message'][$m]['endBalance'] = trim($line);
			}
		}
		assertType("array{header: array{array{name: string}}, message: non-empty-array<int<0, max>, array{header?: array{_pointer: 'header[0][id]'}, endBalance?: string, startSign?: string, startDate?: string, startCurrency?: string, startBalance?: string, number?: string, sequence?: string, reference?: string, account?: string}>, transaction?: non-empty-array<int, array{description: string}>}", $data);
		foreach ($data['transaction'] as $transaction) {
		}
	}
}
