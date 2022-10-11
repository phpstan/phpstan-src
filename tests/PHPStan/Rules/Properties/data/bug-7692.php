<?php

namespace InheritingNamespace7692 {

	use BaseNamespace7692\TheBaseService;
	//use BaseNamespace\Entity\EntityBaseInterface;

	class TheIntermediateService extends TheBaseService
	{

	}
}

namespace BaseNamespace7692 {

	use BaseNamespace7692\Entity\EntityBaseInterface;

	class TheBaseService
	{
		/**
		 * @var class-string<EntityBaseInterface>
		 */
		protected static $entityClass;
	}
}

namespace BaseNamespace7692\Entity {

	interface EntityBaseInterface
	{

	}
}

namespace DeepInheritingNamespace7692 {

	use InheritingNamespace7692\TheIntermediateService;
	use DeepInheritingNamespace7692\Entity\TheEntity;

	final class TheChildService extends TheIntermediateService
	{
		protected static $entityClass = TheEntity::class;
	}
}

namespace DeepInheritingNamespace7692\Entity {

	use BaseNamespace7692\Entity\EntityBaseInterface;

	final class TheEntity implements EntityBaseInterface
	{

	}
}
