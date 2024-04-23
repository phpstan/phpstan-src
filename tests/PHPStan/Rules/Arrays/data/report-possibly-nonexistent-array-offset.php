<?php

namespace NonexistentOffset;

class Feature7553
{
	public function arrayWithPossiblyUndefinedArrayOffset(array $array)
	{
		return $array['foo'];
	}

	public function arrayAccessWithPossiblyUndefinedArrayOffset(\ArrayAccess $a): void
	{
		echo $a['test'];
	}

	public function constantArrayWithPossiblyUndefinedArrayOffset(string $s): void
	{
		$a = ['foo' => 1];
		echo $a[$s];
	}
}
