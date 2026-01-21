<?php // lint >= 8.1

namespace Bug13987;

return new class {
	public function getter(): never
	{
		throw self::failure();
	}

	private static function failure(): \LogicException
	{
		return new \LogicException('Aha.');
	}

};
