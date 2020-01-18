<?php

namespace UnreachableIfBranchesNotPhpDoc;

class Foo
{

	/**
	 * @param self $phpDocSelf
	 */
	public function doFoo(
		self $self,
		$phpDocSelf
	)
	{
		if ($self instanceof self) {

		} elseif (rand(0, 1)) {

		}

		if (rand(0, 1)) {

		} elseif (rand(0, 1)) {

		} elseif ($self instanceof self) {

		} else {

		}

		if (rand(0, 1)) {

		} elseif (rand(0, 1)) {

		} elseif ($self instanceof self) {

		} elseif (rand(0, 1)) {

		}

		if ($phpDocSelf instanceof self) {

		} elseif (rand(0, 1)) {

		}

		if (rand(0, 1)) {

		} elseif (rand(0, 1)) {

		} elseif ($phpDocSelf instanceof self) {

		} else {

		}

		if (rand(0, 1)) {

		} elseif (rand(0, 1)) {

		} elseif ($phpDocSelf instanceof self) {

		} elseif (rand(0, 1)) {

		}
	}

}
