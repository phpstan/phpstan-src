<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Arguments;

final class Positional
{

	public function __construct(private bool $checkUninitializedProperties, private OnlyNamed $named)
	{
	}

	public function get(): string
	{
		return '';
	}

}
