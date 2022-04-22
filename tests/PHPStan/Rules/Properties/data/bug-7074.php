<?php

namespace Bug7074;


abstract class Model
{
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';
}

class SomeModel extends Model
{
	protected $primaryKey = ['abc'];
}

class SomeModel2 extends Model
{
	protected $primaryKey = [2.2];
}
