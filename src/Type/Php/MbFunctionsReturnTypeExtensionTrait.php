<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

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

	private function isSupportedEncoding(string $encoding): bool
	{
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
				$supportedEncodings = array_merge($supportedEncodings, mb_encoding_aliases($encoding), [$encoding]);
			}
		}
		$this->supportedEncodings = array_map('strtoupper', $supportedEncodings);

		return $this->supportedEncodings;
	}

}
