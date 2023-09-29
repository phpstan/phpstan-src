<?php

namespace Bug9905;

interface Foo {
	/** @return array{field: string, extra?: string} */
	public function get(): array;
}

class AlwaysExtra implements Foo {
	/** @return array{field: string, extra: string} */
	public function get(): array {
		return ['field' => 'user', 'extra' => 'readonly'];
	}
}

class NeverExtra implements Foo {
	/** @return array{field: string} */
	public function get(): array {
		return ['field' => 'user'];
	}
}
