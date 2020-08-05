<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;

class FriendTag
{

	private \PHPStan\Type\TypeWithClassName $type;

	private ?string $method;

	public function __construct(TypeWithClassName $type, ?string $method)
	{
		$this->type = $type;
		$this->method = $method;
	}

	public function getType(): TypeWithClassName
	{
		return $this->type;
	}

	public function getMethod(): ?string
	{
		return $this->method;
	}

	public function __toString(): string
	{
		$string = $this->type->describe(VerbosityLevel::precise());
		if ($this->method !== null) {
			$string .= '::' . $this->method;
		}
		return $string;
	}

}
