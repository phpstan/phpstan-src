<?php

namespace Bug3469;

abstract class Tile{
	abstract protected function readSaveData() : void;

	abstract protected function writeSaveData() : void;
}


abstract class Spawnable extends Tile{
}

trait NameableTrait{

	protected function loadName() : void{
	}

	protected function saveName() : void{
	}
}

class EnchantTable extends Spawnable{
	use NameableTrait {
		loadName as readSaveData;
		saveName as writeSaveData;
	}
}
