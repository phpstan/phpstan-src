<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7434;

interface Contract
{
	public function method(string $val): void;
}

class Implementation implements Contract
{
	public function method(string $val): void
	{

	}
}

class ImplementationWithDifferentName implements Contract
{
	public function method(string $wrong): void
	{

	}
}

function takesContract(Contract $contract): void
{
	$contract->method(val: 'string');
}
