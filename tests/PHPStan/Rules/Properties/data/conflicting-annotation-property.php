<?php

namespace ConflictingAnnotationProperty;

/**
 * @property-read int $test
 */
class PropertyWithAnnotation
{

	private $test;

	public function doFoo()
	{
		$this->test = 1;
	}

	public function doFoo2()
	{
		echo $this->test;
	}

}

function (PropertyWithAnnotation $p): void {
	echo $p->test;
	$p->test = 1;
};
