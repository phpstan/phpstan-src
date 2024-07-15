<?php declare(strict_types=1);

namespace Bug11207;

final class FilterData
{
	/**
	 * @phpstan-pure
	 */
	private function __construct(
		public ?int $type,
		public bool $hasValue,
		public mixed $value = null
	) {
	}

	/**
	 * @param array{type?: int|numeric-string|null, value?: mixed} $data
	 * @phpstan-pure
	 */
	public static function fromArray(array $data): self
	{
		if (isset($data['type'])) {
			if (!\is_int($data['type']) && (!\is_string($data['type']) || !is_numeric($data['type']))) {
				throw new \InvalidArgumentException(sprintf(
					'The "type" parameter MUST be of type "integer" or "null", "%s" given.',
					\gettype($data['type'])
				));
			}

			$type = (int) $data['type'];
		} else {
			$type = null;
		}

		return new self($type, \array_key_exists('value', $data), $data['value'] ?? null);
	}
}
