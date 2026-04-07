<?php declare(strict_types = 1);

namespace Bug13705Rule;

function whileLoopWithInArray(): void
{
	$quantity = random_int(1, 42);
	$codes = [];
	while (count($codes) < $quantity) {
		$code = random_bytes(16);
		if (!in_array($code, $codes, true)) {
			$codes[] = $code;
		}
	}
}

function whileLoopOriginal(int $length, int $quantity): void
{
	if ($length < 8) {
		throw new \InvalidArgumentException();
	}
	$codes = [];
	while ($quantity >= 1 && count($codes) < $quantity) {
		$code = '';
		for ($i = 0; $i < $length; $i++) {
			$code .= 'x';
		}
		if (!in_array($code, $codes, true)) {
			$codes[] = $code;
		}
	}
}

class HelloWorld
{
	private const MIN_LENGTH = 8;

	/**
	 * @return list<non-empty-string>
	 */
	public function generatePlainRecoveryCodes(int $length = 8, int $quantity = 8): array
	{
		if ($length < self::MIN_LENGTH) {
			throw new \InvalidArgumentException(
				$length . ' is not allowed as length for recovery codes. Must be at least ' . self::MIN_LENGTH,
				1613666803
			);
		}
		$codes = [];
		while ($quantity >= 1 && count($codes) < $quantity) {
			$code = '';
			for ($i = 0; $i < $length; $i++) {
				$code .= 'x';
			}
			if (!in_array($code, $codes, true)) {
				$codes[] = $code;
			}
		}
		return $codes;
	}
}
