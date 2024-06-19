<?php // lint >= 8.1

namespace Bug10493;

class Foo
{
	public function __construct(
		private readonly ?string $old,
		private readonly ?string $new,
	)
	{
	}

	public function foo(): ?string
	{
		$return = sprintf('%s%s', $this->old, $this->new);

		if ($return === '') {
			return null;
		}

		return $return;
	}
}
