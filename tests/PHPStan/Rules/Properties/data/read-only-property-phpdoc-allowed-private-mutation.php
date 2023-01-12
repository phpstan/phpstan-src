<?php // lint >= 8.0

namespace ReadOnlyPropertyPhpDocAllowedPrivateMutation;

class A
{

	/** @phpstan-readonly */
	public array $a = [];

}

class B
{

	/**
	 * @phpstan-readonly
	 * @phpstan-allow-private-mutation
	 */
	public array $a = [];

}

class C
{

	/** @phpstan-readonly-allow-private-mutation */
	public array $a = [];

}
