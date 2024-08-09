<?php declare(strict_types = 1);

namespace Bug11276;

class HelloWorld
{
	/**
	 * @param array{from: string, to: string} $expected
	 */
	public function testLanguagesMatchingRegex(string $url, ?array $expected): void
	{
		preg_match('#\/(?<from>[a-z]{2})-(?<to>[a-z]{1}[a-z0-9]{1})\/#', $url, $matches);

		foreach ($expected as $key => $value) {
			if ($matches instanceof ArrayAccess || \array_key_exists($key, $matches)) {
				$matches[$key];
			}
		}

		foreach ($expected as $key => $value) {
			if (\array_key_exists($key, $matches) || $matches instanceof ArrayAccess) {
				$matches[$key];
			}
		}

		foreach ($expected as $key => $value) {
			if (!$matches instanceof ArrayAccess && !\array_key_exists($key, $matches)) {
			} else {
				$matches[$key];
			}
		}

		foreach ($expected as $key => $value) {
			if (!\array_key_exists($key, $matches) && !$matches instanceof ArrayAccess) {
			} else {
				$matches[$key];
			}
		}
	}
}
