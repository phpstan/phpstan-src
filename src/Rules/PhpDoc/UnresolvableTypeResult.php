<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Turbo\ReferencedByTurboExtension;

#[ReferencedByTurboExtension(key: 'unresolvableTypeResult')]
final class UnresolvableTypeResult
{

	/** @param list<string> $reasons */
	public function __construct(public readonly array $reasons)
	{
	}

}
