<?php // lint >= 8.4

namespace ExistingClassesPropertyHooks;

class Demo {

	public mixed $get {
		#[\NoDiscard]
		get => true;
	}

	public mixed $set {
		#[\NoDiscard]
		set => false;
	}

}
