<?php

function testClassExistsFalseNotRemembered(): void
{
	if (!class_exists('Bug8579FalseNotRememberedA')) {
		// class_exists returned false here, but we don't exit
	}

	// The false result must not be permanently remembered,
	// so a subsequent class_exists check can still return true
	if (class_exists('Bug8579FalseNotRememberedA')) {
		$y = new \Bug8579FalseNotRememberedA();
	}
}

function testClassExistsFalseNotRememberedElse(): void
{
	if (class_exists('Bug8579FalseNotRememberedB')) {
		$y = new \Bug8579FalseNotRememberedB();
	} else {
		// class_exists returned false in this branch
	}

	// After the else branch, the false result must not stick
	if (class_exists('Bug8579FalseNotRememberedB')) {
		$z = new \Bug8579FalseNotRememberedB();
	}
}
