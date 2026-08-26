<?php // lint >= 8.1

namespace MethodNeverPhp80;

class MagicMethods
{

	public function __clone(): never
	{
		throw new \Exception();
	}

	public function __toString(): never
	{
		throw new \Exception();
	}

}
