<?php

namespace Bug7017;

class Test {
	public function close(\finfo $finfo): void {
		finfo_close($finfo);
	}
}
