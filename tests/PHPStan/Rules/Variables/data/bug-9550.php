<?php declare(strict_types = 1);

namespace Bug9550;

class Foo
{
	protected static function makeShortTextFromScalar(string $value, int $maxUtf8Length = 50): string
	{
		$maxUtf8Length = max(20, min($maxUtf8Length, 100));

		$vStrLonger = mb_substr($value, 0, $maxUtf8Length + 1000);

		$withThreeDots = false;
		\PHPStan\dumpType($maxUtf8Length);
		for ($l = $maxUtf8Length; $l > 0; --$l) {
			$vStr = mb_substr($vStrLonger, 0, $l);
			if ($vStr !== $vStrLonger) {
				$vStrLonger = $vStr;
				$vStr = mb_substr($vStr, 0, $l - 3);
				$withThreeDots = true;
			} else {
				$vStrLonger = $vStr;
			}
			$vStr = str_replace(["\0", "\t", "\n", "\r"], ['\0', '\t', '\n', '\r'], $vStr);
			if (mb_strlen($vStr) <= $maxUtf8Length - ($withThreeDots ? 3 : 0)) {
				break;
			}
		}

		return '\'' . $vStr . '\'' . ($withThreeDots ? '...' : '');
	}
}
