<?php

namespace Bug13831 {
	// non-global namespace block
}

namespace {

	/** @return list<string> */
	function bug13831Qux(): array {
		return [ random_bytes(16) ];
	}

}
