<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[ReferencedByTurboExtension(key: 'unresolvableTypeResult')]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/UnresolvableTypeResult.cpp')]
final class UnresolvableTypeResult
{

	/** @param list<string> $reasons */
	public function __construct(public readonly array $reasons)
	{
	}

}
