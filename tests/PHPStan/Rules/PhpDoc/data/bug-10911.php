<?php // lint >= 8.3

namespace Bug10911;

abstract class Model
{
	/**
	 * The name of the "created at" column.
	 *
	 * @var string|null
	 */
	const CREATED_AT = 'created_at';

	/**
	 * The name of the "updated at" column.
	 *
	 * @var string|null
	 */
	const UPDATED_AT = 'updated_at';
}

class TestModel extends Model
{
	const string CREATED_AT = 'data_criacao';
	const string UPDATED_AT = 'data_alteracao';
	const DELETED_AT = null;
}
