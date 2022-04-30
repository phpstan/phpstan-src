<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTimeImmutable;

class DateTimeImmutableModifyReturnTypeExtension extends DateTimeModifyReturnTypeExtension
{

	public function getClass(): string
	{
		return DateTimeImmutable::class;
	}

}
