<?php

namespace ListReturnTypeCovariance;

interface ListParent
{
	/** @return list<string> */
	public function returnsList();

	/** @return array<int, string> */
	public function returnsArray();
}

interface ListChild extends ListParent
{
	/** @return array<int, string> */
	public function returnsList();

	/** @return list<string> */
	public function returnsArray();
}
