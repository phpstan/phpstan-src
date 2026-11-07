<?php

namespace GlobalConstantWildcard {

	const FLAG_READ = 'read';
	const FLAG_WRITE = 'write';
	const OTHER_FLAG = 'other';

	define('GLOBAL_CONSTANT_WILDCARD_LOW', 10);
	define('GLOBAL_CONSTANT_WILDCARD_HIGH', 100);

}

namespace GlobalConstantWildcard\Sub {

	const PHP_ROUND_SHADOWED = 'shadowed';
	const FLAG_EXECUTE = 'execute';

}
