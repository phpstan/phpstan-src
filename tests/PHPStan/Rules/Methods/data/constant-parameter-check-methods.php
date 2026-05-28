<?php

namespace ConstantParameterCheckMethods;

// finfo::file - correct constant
$finfo = new \finfo(FILEINFO_MIME);
$finfo->file('test.txt', FILEINFO_MIME_TYPE);

// finfo::file - wrong constant
$finfo->file('test.txt', SORT_REGULAR);

// PDOStatement::fetch - correct class constant
/** @var \PDOStatement $stmt */
$stmt->fetch(\PDO::FETCH_ASSOC);

// PDOStatement::fetch - wrong class constant
$stmt->fetch(\PDO::ATTR_ERRMODE);

// Collator::sort - correct class constant
/** @var \Collator $collator */
$arr = [];
$collator->sort($arr, \Collator::SORT_STRING);

// Collator::sort - wrong class constant
$collator->sort($arr, \Collator::FRENCH_COLLATION);

// PDOStatement::fetch - wrong class constant via named argument (multi-variant method)
$stmt->fetch(mode: \PDO::ATTR_ERRMODE);

// PDOStatement::setFetchMode - exclusive base modes via named argument (multi-variant method)
$stmt->setFetchMode(mode: \PDO::FETCH_ASSOC | \PDO::FETCH_NUM);
