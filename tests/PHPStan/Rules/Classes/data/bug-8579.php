<?php

if (!class_exists('NonexistentClassBug8579')) throw new \Exception('nonexistentclass');
$x = new \NonexistentClassBug8579();

if (!interface_exists('NonexistentInterfaceBug8579')) throw new \Exception('nonexistentinterface');
$x = new \NonexistentInterfaceBug8579();

if (!trait_exists('NonexistentTraitBug8579')) throw new \Exception('nonexistenttrait');
$x = new \NonexistentTraitBug8579();

if (!enum_exists('NonexistentEnumBug8579')) throw new \Exception('nonexistentenum');
$x = new \NonexistentEnumBug8579();
