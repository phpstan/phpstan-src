<?php

spl_autoload_register(function ($class) {
	if ($class === 'index') {
		throw new LogicException("Autoloader should not be called for 'index'");
	}
});
