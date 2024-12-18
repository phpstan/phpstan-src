<?php // lint >= 8.4

namespace PropertyHooksReadonlyByPhpDocAssign;

class Foo
{

	/** @readonly */
	public int $i {
		get {
			return $this->i + 1;
		}
		set {
			$self = new self();
			$self->i = 1;

			$this->j = 2;
			$this->i = $value - 1;
		}
	}

	/** @readonly */
	public int $j;

}
