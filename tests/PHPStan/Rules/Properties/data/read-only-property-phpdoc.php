<?php // lint >= 8.1

namespace ReadOnlyPropertyPhpDoc;

class Foo
{

	/**
	 * @readonly
	 * @var int
	 */
	private $foo;

	/** @readonly */
	private $bar;

	/**
	 * @readonly
	 * @var int
	 */
	private $baz = 0;

}

final class ErrorResponse
{
	public function __construct(
		/** @readonly */
		public string $message = ''
	)
	{
	}
}
