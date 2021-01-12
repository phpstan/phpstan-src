<?php

namespace Bug4214;

trait AbstractTrait {
	abstract public function getMessage();
}

class Test extends \Exception {
	use AbstractTrait;
}
