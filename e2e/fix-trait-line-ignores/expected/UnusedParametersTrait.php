<?php declare(strict_types = 1);

namespace TraitLineIgnores;

trait UnusedParametersTrait
{

	/** @phpstan-ignore constructor.unusedParameter (Called by the DI container), constructor.unusedParameter (Called by the DI container), constructor.unusedParameter (Called by the DI container) */
	public function __construct(string $a, string $b, string $c)
	{
	}

}
