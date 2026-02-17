<?php // lint >= 8.0

namespace Bug11100;

final class FooClass
{
	/**
	 * @param array<string, string|int> $getParams
	 *
	 * @phpstan-pure
	 */
	public function build(array $getParams, string $trailingSlash, bool $useSlashSeparator): string
	{
		if ($getParams === []) {
			return '';
		}

		if ($useSlashSeparator) {
			return '/' . implode('/', array_map(
				static function (string $key, string|int $value): string {
					return rawurlencode($key) . '/' . rawurlencode((string) $value);
				},
				array_keys($getParams),
				$getParams
			)) . $trailingSlash;
		}

		return $trailingSlash . '?' . http_build_query($getParams);
	}
}
