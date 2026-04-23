<?php declare(strict_types = 1);

namespace Bug13272;

class Paginated
{
	public function setPageSize(int $i): void
	{
		echo "blah";
	}
	public function setPageNumber(int $i): void
	{
		echo "blah";
	}
	public function findThis(): void
	{
		echo "blah";
	}
}

function doSomething(object $o, string $findMethod): void
{
	foreach (['setPageSize', 'setPageNumber'] as $method) {
	    if (! method_exists($o, $method)) {
			throw new \Exception("");
		}
		if (! method_exists($o, $findMethod)) {
			throw new \Exception("");
		}
	}
	$o->setPageSize(1);
	$o->setPageNumber(3);
	$reult = $o->$findMethod();  // oddly enough, this works, despite above two not
}

$p = new Paginated();
doSomething($p, 'findThis');
$p->setPageNumber(1);
$p->setPageSize(3);
$p->findThis();
