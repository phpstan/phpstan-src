<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9386;

trait BaseTrait {
	protected false|int $_pos;

	public function myMethod():bool {
		$pos = $this->_pos;
		if ($pos === false)
			return false;
		if (($this instanceof BaseClass) && $this->length !== null)
			return $pos >= $this->offset + $this->length;
		return false;
	}
}

class BaseClass
{
	use BaseTrait;
	protected ?int $length = null;
	protected int $offset = 0;
}
class SecondClass
{
	use BaseTrait;
}
