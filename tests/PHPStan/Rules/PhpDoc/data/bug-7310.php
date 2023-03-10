<?php

namespace Bug7310;

/**
 * @template M of array<string, mixed>
 */
class ObjectWithMetadata {

	/**
	 * @param M $metadata
	 */
	public function __construct(
		public array $metadata
	) {
	}

	/**
	 * @template K of string
	 * @template D
	 * @param K $key
	 * @param D $default
	 * @return (M[K] is not null ? M[K] : D)
	 */
	public function getMeta(string $key, mixed $default = null): mixed
	{
		return $this->metadata[ $key ] ?? $default;
	}
}
