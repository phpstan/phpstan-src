<?php // lint >= 8.0

namespace Bug5868;

class HelloWorld
{
	public function nullable1(): ?self
	{
		// OK
		$tmp = $this->nullable1()?->nullable1()?->nullable2();
		$tmp = $this->nullable1()?->nullable3()->nullable2()?->nullable3()->nullable1();

		// Error
		$tmp = $this->nullable1()->nullable1()?->nullable2();
		$tmp = $this->nullable1()?->nullable1()->nullable2();
		$tmp = $this->nullable1()?->nullable3()->nullable2()->nullable3()->nullable1();

		return $this->nullable1()?->nullable3();
	}

	public function nullable2(): ?self
	{
		return $this;
	}

	public function nullable3(): self
	{
		return $this;
	}
}
