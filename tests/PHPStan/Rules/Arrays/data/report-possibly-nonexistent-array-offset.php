<?php

namespace NonexistentOffset;

class Feature7553
{
	public function arrayWithPossiblyUndefinedArrayOffset(array $array)
	{
		return $array['foo'];
	}
}
