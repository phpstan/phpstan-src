<?php

namespace NoOptionalParameter;

function internalFunction(int $required, int $optional = 1, int ...$variadic): void
{
}

/** @api */
function apiFunction(int $optional = 1): void
{
}

class InternalClass
{

	public function __construct(int $optional = 1)
	{
	}

	public function method(int $required, ?int $optional = null): void
	{
		$closure = static function (int $optional = 1): void {
		};
		$arrowFunction = static fn (int $optional = 1): int => $optional;
	}

	/** @api */
	public function apiMethod(int $optional = 1): void
	{
	}

}

/** @api */
class ApiClass
{

	public function __construct(int $optional = 1)
	{
	}

	public function publicMethod(int $optional = 1): void
	{
	}

	protected function protectedMethod(int $optional = 1): void
	{
	}

	private function privateMethod(int $optional = 1): void
	{
	}

}

/** @api */
final class FinalApiClass
{

	/** @api */
	public function __construct(int $optional = 1)
	{
	}

	protected function protectedMethod(int $optional = 1): void
	{
	}

}

/** @api-do-not-implement */
class DoNotImplementOnlyClass
{

	public function method(int $optional = 1): void
	{
	}

}

interface InternalInterface
{

	public function method(int $a, int $b = 1): void;

}

class Implementation implements InternalInterface
{

	public function method(int $a, int $b = 1, int $c = 2): void
	{
	}

}

class ChildOfInternalClass extends InternalClass
{

	public function method(int $required, ?int $optional = null, int $another = 1): void
	{
	}

}

class ThirdPartyChild extends \Exception
{

	public function __construct(string $message = '', int $code = 0)
	{
		parent::__construct($message, $code);
	}

}

trait TraitImplementingInterface
{

	public function method(int $a, int $b = 1, int $c = 2): void
	{
	}

}

class UsesTraitImplementingInterface implements InternalInterface
{

	use TraitImplementingInterface;

}
