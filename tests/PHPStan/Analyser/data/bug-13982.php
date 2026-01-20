<?php

namespace Bug13982;

function (): void {
	$v = new class {
		public function test2(): string {
			return self::test();
		}
		private static function test(): string {
			return 'result';
		}
	};

	$v->test2();
};
