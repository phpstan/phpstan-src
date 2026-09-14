<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;

/** @api */
#[ReferencedByTurboExtension(key: 'lateResolvableType')]
interface LateResolvableType
{

	public function resolve(): Type;

	public function isResolvable(): bool;

}
