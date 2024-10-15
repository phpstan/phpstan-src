<?php

namespace Generics\Bug9630;

/**
 * @template T of A
 */
trait T1
{
	/**
	 * @use T2<template-type<T, A, 'T'>>
	 */
	use T2;

	/**
	 * @param T $p
	 * @return template-type<T, A, 'T'>
	 */
	public function getParam(A $p): ?A
	{
		return $this->getParamFromT2($p->getOther());
	}
}

