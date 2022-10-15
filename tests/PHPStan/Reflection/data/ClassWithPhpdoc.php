<?php

namespace FunctionReflectionDocTest;

class ClassWithPhpdoc {
	public function __construct() {

	}

	/** some method phpdoc */
	public function aMethod() {

	}
	/** existing method phpdoc */
	public function existingDocButStubOverridden() {

	}
	public function noDocMethod() {

	}
	public function docViaStub() {

	}
}
