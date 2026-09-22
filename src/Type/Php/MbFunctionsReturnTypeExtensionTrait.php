<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\Scope;
use PHPStan\ShouldNotHappenException;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function function_exists;
use function in_array;
use function mb_encoding_aliases;
use function mb_list_encodings;
use function strtoupper;

trait MbFunctionsReturnTypeExtensionTrait
{

	/** @var array<int, string[]> */
	private array $supportedEncodings = [];

	private function isSupportedEncoding(string $encoding, Scope $scope): bool
	{
		return in_array(strtoupper($encoding), $this->getSupportedEncodings($scope), true);
	}

	/** @return string[] */
	private function getSupportedEncodings(Scope $scope): array
	{
		// PHP 7.3 and 7.4 claims 'pass' and its alias 'none' to be supported, but actually 'pass' was removed in 7.3.
		// When the analysed PHP version range spans that boundary, keep them supported to stay on the safe side.
		$withPassNone = !$scope->getPhpVersion()->supportsPassNoneEncodings()->no();
		$cacheKey = (int) $withPassNone;
		if (array_key_exists($cacheKey, $this->supportedEncodings)) {
			return $this->supportedEncodings[$cacheKey];
		}

		$supportedEncodings = [];
		if (function_exists('mb_list_encodings')) {
			foreach (mb_list_encodings() as $encoding) {
				$aliases = @mb_encoding_aliases($encoding);
				if ($aliases === false) {
					throw new ShouldNotHappenException();
				}
				$supportedEncodings = array_merge($supportedEncodings, $aliases, [$encoding]);
			}
		}
		$supportedEncodings = array_map('strtoupper', $supportedEncodings);

		if (!$withPassNone) {
			$supportedEncodings = array_filter(
				$supportedEncodings,
				static fn (string $enc) => !in_array($enc, ['PASS', 'NONE'], true),
			);
		}

		return $this->supportedEncodings[$cacheKey] = $supportedEncodings;
	}

}
