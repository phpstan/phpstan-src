<?php declare(strict_types = 1);

namespace Bug4999;

class HelloWorld
{
	/**
	 * @param mixed[] $array
	 */
	public function extractPhone(array $array): void
	{
		$phone = null;

		if (array_key_exists('PHONE', $array)) {
			while (null === $phone && is_array($value = array_shift($array['PHONE']))) {
				$phone = self::parsePhone($value['VALUE']);
			}
		}
	}

	private static function parsePhone($something): ?string
	{
		return null === $something ? null : '911';
	}
}
