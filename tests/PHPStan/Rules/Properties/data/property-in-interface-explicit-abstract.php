<?php

namespace PropertyInInterfaceExplicitAbstract;

// https://3v4l.org/79QMR#v8.4.4
interface Foo
{
	abstract public int $i {
		set;
	}

	public int $y {
		set;
	}
}
