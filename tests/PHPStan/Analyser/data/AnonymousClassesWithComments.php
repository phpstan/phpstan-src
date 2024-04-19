<?php

// Test comment
$a = new class () {

	public function doFoo(): void
	{
		$this->doBar();
	}

};

$a = /* Test comment */ new class () {

	public function doFoo(): void
	{
		$this->doBar();
	}

};

$a = /** Test comment */ new class () {

	public function doFoo(): void
	{
		$this->doBar();
	}

};
