<?php

namespace Bug7057;

function doFoo() {
	// error
	echo vsprintf('aa %s bb', call_user_func(function (): array { return ['a']; }));

	// but ok
	echo vsprintf('aa %s bb', (function (): array { return ['a']; })());
}
