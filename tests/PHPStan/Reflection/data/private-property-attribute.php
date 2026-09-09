<?php // lint >= 8.1

declare(strict_types = 1);

namespace PrivatePropertyAttribute;

use PHPStan\Reflection\Attribute\PrivateProperty;
use PHPStan\Reflection\Attribute\ProtectedProperty;

class Foo
{

	#[PrivateProperty]
	public int $madePublic = 1;

	#[ProtectedProperty]
	public int $madePublicFromProtected = 2;

	public int $reallyPublic = 3;

	private int $reallyPrivate = 4;

	public function __construct(
		#[PrivateProperty]
		public readonly string $promoted = 'x',
	)
	{
	}

}
