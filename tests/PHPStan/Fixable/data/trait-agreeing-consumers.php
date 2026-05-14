<?php

trait TraitAgreeingConsumers {
	public function f(): void {
		echo $a;
	}
}

class TraitAgreeingConsumerOne {
	use TraitAgreeingConsumers;
}

class TraitAgreeingConsumerTwo {
	use TraitAgreeingConsumers;
}
