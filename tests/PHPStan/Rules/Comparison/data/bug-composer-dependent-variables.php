<?php

namespace BugComposerDependentVariables;

function removeSubNode($mainNode, $name)
{
	/** @var mixed $decoded */
	$decoded = doFoo();

	if (empty($decoded[$mainNode])) {
		return true;
	}

	$subName = null;
	if (in_array($mainNode, array('config', 'extra', 'scripts')) && false !== strpos($name, '.')) {
		list($name, $subName) = explode('.', $name, 2);
	}

	if (!isset($decoded[$mainNode][$name]) || ($subName && !isset($decoded[$mainNode][$name][$subName]))) {
		return true;
	}
}
