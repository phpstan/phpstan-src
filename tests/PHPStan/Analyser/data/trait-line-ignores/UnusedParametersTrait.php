<?php declare(strict_types = 1);

namespace TraitLineIgnores;

trait UnusedParametersTrait
{

	/** @phpstan-ignore-next-line Called by the DI container */
	public function __construct(string $a, string $b, string $c)
	{
	}

}
