<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/** @api */
class PropertyTag
{

	public function __construct(
		private Type $type,
		private bool $readable,
		private bool $writable,
	)
	{
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function isReadable(): bool
	{
		return $this->readable;
	}

	public function isWritable(): bool
	{
		return $this->writable;
	}

}
