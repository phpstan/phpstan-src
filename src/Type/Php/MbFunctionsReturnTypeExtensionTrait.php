<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function array_merge;
use function function_exists;
use function in_array;
use function is_null;
use function mb_encoding_aliases;
use function mb_list_encodings;
use function strtoupper;

trait MbFunctionsReturnTypeExtensionTrait
{

	/** @var string[]|null */
	private ?array $supportedEncodings = null;

	private function isSupportedEncoding(string $encoding, PhpVersion $phpVersion): bool
	{
		// PHP 7.3 and 7.4 claims 'pass' and its alias 'none' to be supported, but actually 'pass' was removed in 7.3
		if (in_array($encoding, ['pass', 'none'], true) && !$phpVersion->supportsPassNoneEncodings()) {
			return false;
		}
		return in_array(strtoupper($encoding), $this->getSupportedEncodings(), true);
	}

	/** @return string[] */
	private function getSupportedEncodings(): array
	{
		if (!is_null($this->supportedEncodings)) {
			return $this->supportedEncodings;
		}

		$supportedEncodings = [];
		if (function_exists('mb_list_encodings')) {
			foreach (mb_list_encodings() as $encoding) {
				$aliases = mb_encoding_aliases($encoding);
				if ($aliases === false) {
					throw new ShouldNotHappenException();
				}
				$supportedEncodings = array_merge($supportedEncodings, $aliases, [$encoding]);
			}
		}
		$this->supportedEncodings = array_map('strtoupper', $supportedEncodings);

		return $this->supportedEncodings;
	}

}
