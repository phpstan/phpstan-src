<?php // lint >= 8.4

declare(strict_types = 1);

namespace UnusedLabelPropertyHook;

class Foo
{

	public int $value {
		get {
			goto end;
			echo "unreachable";
			end:
			return 42;
		}
	}

	public int $broken {
		get {
			unused:
			return 0;
		}
	}

}
