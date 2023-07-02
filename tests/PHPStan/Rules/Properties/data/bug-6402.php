<?php // lint >= 8.1

namespace Bug6402;

class SomeModel
{
	public readonly ?int $views;

	public function __construct(string $mode, int $views)
	{
		if ($mode === 'mode1') {
			$this->views = $views;
		} else {
			$this->views = null;
		}
	}
}

class SomeModel2
{
	public readonly ?int $views;

	public function __construct(string $mode, int $views)
	{
		if ($mode === 'mode1') {
			$this->views = $views;
		} else {
			echo $this->views;
			$this->views = null;
		}
	}
}
