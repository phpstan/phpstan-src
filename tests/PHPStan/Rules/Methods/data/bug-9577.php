<?php // lint >= 8.1

namespace Bug9577IllegalConstructorStaticCall;

trait StringableMessageTrait
{
	public function __construct(
		private readonly \Stringable $StringableMessage,
		int $code = 0,
		?\Throwable $previous = null,
	) {
		parent::__construct((string) $StringableMessage, $code, $previous);
	}

	public function getStringableMessage(): \Stringable
	{
		return $this->StringableMessage;
	}
}

class SpecializedException extends \RuntimeException
{
	use StringableMessageTrait {
		StringableMessageTrait::__construct as __traitConstruct;
	}

	public function __construct(
		private readonly object $aService,
		\Stringable $StringableMessage,
		int $code = 0,
		?\Throwable $previous = null,
	) {
		$this->__traitConstruct($StringableMessage, $code, $previous);
	}

	public function getService(): object
	{
		return $this->aService;
	}
}
