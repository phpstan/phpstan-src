<?php

namespace Generics\Bug9630;

/**
 * @implements B<A1>
 */
class C1 implements B
{
	/**
	 * @use T1<A2>
	 */
	use T1;

	public function f(): ?A
	{
		return $this->getParam(new A2());
	}
}
