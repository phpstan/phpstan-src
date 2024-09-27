<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/**
 * @api
 */
final class PropertyTag
{

	public function __construct(
		private ?Type $readableType,
		private ?Type $writableType,
	)
	{
	}

	public function getReadableType(): ?Type
	{
		return $this->readableType;
	}

	public function getWritableType(): ?Type
	{
		return $this->writableType;
	}

	/**
	 * @phpstan-assert-if-true !null $this->getReadableType()
	 */
	public function isReadable(): bool
	{
		return $this->readableType !== null;
	}

	/**
	 * @phpstan-assert-if-true !null $this->getWritableType()
	 */
	public function isWritable(): bool
	{
		return $this->writableType !== null;
	}

}
