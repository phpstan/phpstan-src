<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug11674;

class Test {

	private ?string $param;

	function show() : void {
		if ((int) $this->param) {
			echo 1;
		} elseif ($this->param) {
			echo 2; // might be "0"
		}
	}

	function show2() : void {
		if ((float) $this->param) {
			echo 1;
		} elseif ($this->param) {
			echo 2; // might be "0"
		}
	}

	function show3() : void {
		if ((bool) $this->param) {
			echo 1;
		} elseif ($this->param) {
			echo 2; // not possible
		}
	}

	function show4() : void {
		if ((string) $this->param) {
			echo 1;
		} elseif ($this->param) {
			echo 2; // not possible
		}
	}
}
