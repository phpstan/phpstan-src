<?php // lint >= 8.0

namespace ThrowValuesNullsafe;

class Bar
{

	function doException(): \Exception
	{
		return new \Exception();
	}

}

function doFoo(?Bar $bar)
{
	throw $bar?->doException();
}
