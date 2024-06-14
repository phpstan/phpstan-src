<?php

namespace Bug4714;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{
	public function bar(\PDO $database): void
	{
		$statement = $database->prepare('SELECT `col` FROM `foo` WHERE `param` = :param');
		$statement->bindParam(':param', $param);

		assertVariableCertainty(TrinaryLogic::createYes(), $param);

		$param = 1;

		$statement->execute();
		$statement->bindColumn('col', $col);

		assertVariableCertainty(TrinaryLogic::createYes(), $col);
	}
}
