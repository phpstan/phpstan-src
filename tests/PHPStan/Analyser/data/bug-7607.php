<?php declare(strict_types = 1);

namespace Bug7607;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * Determine if the given value is "blank".
	 *
	 * @param  mixed  $value
	 * @return bool
	 *
	 * @phpstan-assert-if-false !(null|''|array{}) $value
	 */
	public function blank($value)
	{
		if (is_null($value)) {
			return true;
		}

		if (is_string($value)) {
			return trim($value) === '';
		}

		if (is_numeric($value) || is_bool($value)) {
			return false;
		}

		if ($value instanceof Countable) {
			return count($value) === 0;
		}

		return empty($value);
	}

	public function getValue(): string|null
	{
		return 'value';
	}

	public function getUrlForCurrentRequest(): string|null
	{
		return rand(0,1) === 1 ? 'string' : null;
	}

	public function isUrl(string|null $url): bool
	{
		if ($this->blank($url = $url ?? $this->getUrlForCurrentRequest())) {
			return false;
		}

		assertType('non-empty-string', $url);
		$parsed = parse_url($url);

		return is_array($parsed);
	}
}
