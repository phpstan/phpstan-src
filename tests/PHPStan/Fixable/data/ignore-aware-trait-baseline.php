<?php

trait IgnoreAwareTraitBaselineTrait {
	public function f(): void {
		echo $a;
	}
}

class IgnoreAwareTraitBaselineConsumerOne {
	use IgnoreAwareTraitBaselineTrait;
}

class IgnoreAwareTraitBaselineConsumerTwo {
	use IgnoreAwareTraitBaselineTrait;
}
