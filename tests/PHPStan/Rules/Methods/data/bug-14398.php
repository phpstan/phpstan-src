<?php // lint >= 8.0

namespace Bug14398;

#[\Attribute]
class Marker
{

	public function __construct(string $name = '')
	{
	}

}

class ParentClass
{

	public function publicMethod(): void
	{
	}

	public function publicWithDocblock(): void
	{
	}

	protected function protectedMethod(): void
	{
	}

	public function withoutAttribute(): void
	{
	}

}

class ChildClass extends ParentClass
{

	#[\Override]
	private function publicMethod(): void
	{
	}

	/**
	 * Docblock above a multi-line attribute.
	 */
	#[Marker(
		name: 'foo',
	)]
	protected function publicWithDocblock(): void
	{
	}

	#[Marker]
	#[\Override]
	private function protectedMethod(): void
	{
	}

	private function withoutAttribute(): void
	{
	}

}
