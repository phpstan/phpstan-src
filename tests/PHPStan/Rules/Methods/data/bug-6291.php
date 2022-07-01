<?php // lint >= 8.0

namespace Bug6291;

interface Table {}

final class ArticlesTable implements Table
{
	public function __construct(private TableManager $tableManager) {}
	public function find(ArticlePrimaryKey $primaryKey): ?object
	{
		return $this->tableManager->find($this, $primaryKey);
	}
}

/** @template TableType of Table */
interface PrimaryKey {}

/** @implements PrimaryKey<ArticlesTable> */
final class ArticlePrimaryKey implements PrimaryKey {}

class TableManager
{
	/**
	 * @template TableType of Table
	 * @param TableType $table
	 * @param PrimaryKey<TableType> $primaryKey
	 */
	public function find(Table $table, PrimaryKey $primaryKey): ?object
	{
		return null;
	}
}
