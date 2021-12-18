<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/** @api */
class PropertyTag
{

	private Type $type;

	private bool $readable;

	private bool $writable;

	public function __construct(
		Type $type,
		bool $readable,
		bool $writable,
	)
	{
		$this->type = $type;
		$this->readable = $readable;
		$this->writable = $writable;
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
