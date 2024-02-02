<?php // lint >= 8.1

namespace Bug10048;

class Foo
{
	private readonly string $bar;
	private readonly \Closure $callback;

	public function __construct()
	{
		$this->bar = "hi";
		$this->useBar();
		echo $this->bar;
		$this->callback = function () {
			$this->useBar();
		};
	}

	private function useBar(): void
	{
		echo $this->bar;
	}

	public function useCallback(): void
	{
		call_user_func($this->callback);
	}
}

function doFoo() {
	(new Foo())->useCallback();
}
