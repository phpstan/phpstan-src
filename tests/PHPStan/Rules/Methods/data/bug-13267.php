<?php declare(strict_types = 1);

namespace Bug13267;

class ExampleException extends \ErrorException {
	public function __construct(\Throwable $e) {
		parent::__construct(
			$e->getMessage(),
			$e->getCode(),
			1,
			$e->getFile(),
			$e->getLine(),
			$e->getPrevious(),
		);
	}
}
