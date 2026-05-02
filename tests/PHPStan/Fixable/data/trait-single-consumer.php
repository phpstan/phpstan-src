<?php

trait TraitSingleConsumer {
	public function f(): void {
		echo $a;
	}
}

class TraitSingleConsumerUser {
	use TraitSingleConsumer;
}
