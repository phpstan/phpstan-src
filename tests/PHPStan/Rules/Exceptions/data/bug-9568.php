<?php // lint >= 8.0

namespace Bug9568;

class Json
{
	public function decode(
		string $jsonString,
		bool $associative = false,
		int $flags = JSON_THROW_ON_ERROR,
		callable $onDecodeFail = null
	): mixed {
		try {
			return json_decode(
				json: $jsonString,
				associative: $associative,
				flags: $flags,
			);
		} catch (\Throwable $exception) {
			if (isset($onDecodeFail)) {
				return $onDecodeFail($exception);
			}
		}

		return null;
	}
}
