<?php

namespace UnreachableTernaryElseBranchNotPhpDoc;

class Foo
{

	/**
	 * @param self $phpDocSelf
	 */
	public function doFoo(
		self $self,
		$phpDocSelf
	): void
	{
		$self instanceof self ? 'foo' : 'bar';
		$self instanceof self ?: 'bar';

		$phpDocSelf instanceof self ? 'foo' : 'bar';
		$phpDocSelf instanceof self ?: 'bar';
	}

}
