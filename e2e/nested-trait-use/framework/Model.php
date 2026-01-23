<?php

namespace NestedTraitUse\Framework;

class Model
{
	/** @var class-string<Builder> */
	protected static string $builder = Builder::class;

	public function newBuilder(): Builder
	{
		return new static::$builder();
	}
}
