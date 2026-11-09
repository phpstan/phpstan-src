<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use function ord;
use function strlen;

#[AutowiredService]
final class TrimCharactersHelper
{

	/**
	 * Reads the character list like PHP's php_charmask(): "a..z" stands for
	 * every byte from "a" to "z", so "/..:" contains the digits.
	 */
	public function containsDigit(string $characters): bool
	{
		$length = strlen($characters);
		for ($i = 0; $i < $length; $i++) {
			$byte = ord($characters[$i]);
			if (
				$i + 3 < $length
				&& $characters[$i + 1] === '.'
				&& $characters[$i + 2] === '.'
				&& ord($characters[$i + 3]) >= $byte
			) {
				if ($byte <= ord('9') && ord($characters[$i + 3]) >= ord('0')) {
					return true;
				}

				$i += 3;
				continue;
			}

			if ($byte >= ord('0') && $byte <= ord('9')) {
				return true;
			}
		}

		return false;
	}

}
