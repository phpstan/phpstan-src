<?php // lint >= 8.1

namespace Bug9577;

trait StringableMessageTrait
{
	public function __construct(
		public readonly string $message,
	) {

	}
}

class SpecializedException
{
	use StringableMessageTrait {
		StringableMessageTrait::__construct as __traitConstruct;
	}

	public function __construct(
		public int $code,
		string $message,
	) {
		$this->__traitConstruct($message);
	}
}
