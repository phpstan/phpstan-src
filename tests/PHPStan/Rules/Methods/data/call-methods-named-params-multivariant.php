<?php declare(strict_types=1); // lint >= 8.0

namespace CallMethodsNamedParamsMultivariant;


$xslt = new \XSLTProcessor();
$xslt->setParameter(namespace: 'ns', name:'aaa', value: 'bbb');
$xslt->setParameter(namespace: 'ns', name: ['aaa' => 'bbb']);
// wrong
$xslt->setParameter(namespace: 'ns', options: ['aaa' => 'bbb']);

$pdo = new \PDO('123');
$pdo->query(query: 'SELECT 1', fetchMode: \PDO::FETCH_ASSOC);
// wrong
$pdo->query(query: 'SELECT 1', fetchMode: \PDO::FETCH_ASSOC, colno: 1);
// wrong
$pdo->query(query: 'SELECT 1', fetchMode: \PDO::FETCH_ASSOC, className: 'Foo', constructorArgs: []);

$stmt = new \PDOStatement();
$stmt->setFetchMode(mode: 5);
// wrong
$stmt->setFetchMode(mode: 5, className: 'aa');
