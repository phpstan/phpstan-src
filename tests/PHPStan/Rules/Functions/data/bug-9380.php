<?php

namespace Bug9380;

class ErrorLogger {
	public function doFoo() {
		error_log("asd", 2);
	}
}
