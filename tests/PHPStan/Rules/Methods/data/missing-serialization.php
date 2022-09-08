<?php // lint >= 8.1

namespace MissingMagicSerializationMethods;

use Serializable;

abstract class abstractObj implements Serializable {
	public function serialize() {
	}
	public function unserialize($data) {
	}
}

class myObj implements Serializable {
	public function serialize() {
	}
	public function unserialize($data) {
	}
}

enum myEnum implements Serializable {
	case X;
	case Y;

	public function serialize() {
	}
	public function unserialize($data) {
	}
}

abstract class allGood implements Serializable {
	public function serialize() {
	}
	public function unserialize($data) {
	}
	public function __serialize() {
	}
	public function __unserialize($data) {
	}
}
