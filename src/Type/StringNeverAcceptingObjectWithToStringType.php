<?php declare(strict_types = 1);

namespace PHPStan\Type;

class StringNeverAcceptingObjectWithToStringType extends StringType
{

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		return parent::accepts($type, true);
	}

}
