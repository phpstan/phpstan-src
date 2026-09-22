<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Php\PhpVersions;
use PHPStan\ShouldNotHappenException;
use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function function_exists;
use function in_array;
use function mb_encoding_aliases;
use function mb_list_encodings;
use function strtoupper;

trait MbFunctionsReturnTypeExtensionTrait
{

	/** @var string[]|null */
	private ?array $supportedEncodings = null;

	/** @var string[]|null */
	private ?array $supportedEncodingsWithoutPassNone = null;

	private function isSupportedEncoding(string $encoding, PhpVersions $phpVersions): bool
	{
		return in_array(strtoupper($encoding), $this->getSupportedEncodings($phpVersions), true);
	}

	/** @return string[] */
	private function getSupportedEncodings(PhpVersions $phpVersions): array
	{
		// PHP 7.3 and 7.4 claims 'pass' and its alias 'none' to be supported, but actually 'pass' was removed in 7.3
		if ($phpVersions->supportsPassNoneEncodings()->no()) {
			return $this->supportedEncodingsWithoutPassNone ??= array_values(array_filter(
				$this->getAllSupportedEncodings(),
				static fn (string $enc) => !in_array($enc, ['PASS', 'NONE'], true),
			));
		}

		return $this->getAllSupportedEncodings();
	}

	/** @return string[] */
	private function getAllSupportedEncodings(): array
	{
		if ($this->supportedEncodings === null) {
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
			$this->supportedEncodings = array_map('strtoupper', $supportedEncodings);
		}

		return $this->supportedEncodings;
	}

}
