<?php

namespace Bug5062;

class Foo
{

	public function isKnownUOM(string $uom): string
	{
		return (
			($uom === '') ||
			($uom === 's') ||
			($uom === 'ms') ||
			($uom === 'us') ||
			($uom === '%') ||
			($uom === 'B') ||
			($uom === 'KB') ||
			($uom === 'MB') ||
			($uom === 'GB') ||
			($uom === 'TB') ||
			($uom === 'c')
		);
	}

}
