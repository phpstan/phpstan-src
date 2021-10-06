<?php

namespace Bug5744;

class HelloWorld
{

	/**
	 * @phpstan-param mixed[] $plugin
	 */
	public function sayHello(array $plugin): void
	{
		if(isset($plugin["commands"]) and is_array($plugin["commands"])){
			$pluginCommands = $plugin["commands"];
			foreach($pluginCommands as $commandName => $commandData){
				var_dump($commandData["permission"]);
			}
		}
	}

	/**
	 * @phpstan-param mixed[] $plugin
	 */
	public function sayHello2(array $plugin): void
	{
		if(isset($plugin["commands"])){
			$pluginCommands = $plugin["commands"];
			foreach($pluginCommands as $commandName => $commandData){
				var_dump($commandData["permission"]);
			}
		}
	}

	public function sayHello3(array $plugin): void
	{
		if(isset($plugin["commands"]) and is_array($plugin["commands"])){
			$pluginCommands = $plugin["commands"];
			foreach($pluginCommands as $commandName => $commandData){
				var_dump($commandData["permission"]);
			}
		}
	}
}
