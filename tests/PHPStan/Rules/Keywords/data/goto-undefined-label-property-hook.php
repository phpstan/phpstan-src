<?php // lint >= 8.4

declare(strict_types = 1);

namespace GotoUndefinedLabelPropertyHook;

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
			goto nonexistent;
			return 0;
		}
	}

}

class CrossBoundary
{

	public int $value {
		get {
			outside:
			$fn = function () {
				goto outside;
			};
			return 42;
		}
	}

}
