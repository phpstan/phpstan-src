<?php

namespace Bug8500;

interface iDBO {
}

class DBOA implements iDBO
{

}

class DBOB extends DBOA
{

}

interface iDBOH {
	public function test();
}


class DBOHA implements iDBOH {
	public function test(): DBOA {
		return new DBOA();
	}
}

class DBOHB extends DBOHA {
	public function test() {
		return new DBOB();
	}
}
