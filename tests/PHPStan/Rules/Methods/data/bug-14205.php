<?php declare(strict_types = 1);

namespace Bug14205\Competitie\Team;

class TeamsamenstellingService {}

class SamenstellingService {}

namespace Bug14205\Service;

use Bug14205\Competitie\Team\SamenstellingService as TeamSamenstellingService;

abstract class Foo {
	abstract public function test(): TeamSamenstellingService;
}
