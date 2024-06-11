<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use JsonSerializable;
use ReturnTypeWillChange;

class InternalError implements JsonSerializable
{

	public function __construct(
		private string $message,
	)
	{
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * @param mixed[] $json
	 */
	public static function decode(array $json): self
	{
		return new self($json['message']);
	}

	/**
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return [
			'message' => $this->message,
		];
	}

}
