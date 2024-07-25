<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

/**
 * @api
 * @final
 */
class DeprecatedTag
{

	public function __construct(private ?string $message)
	{
	}

	public function getMessage(): ?string
	{
		return $this->message;
	}

}
