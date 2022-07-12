<?php

namespace SensitiveParameter;

class HelloWorld {

	public function __construct(
		#[\SensitiveParameter]
		string $password
	) {
	}

}
