<?php

namespace FunctionReflectionDocTest;

class ClassWithPhpdoc {
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
