<?php // lint >= 8.2

namespace Bug12281Traits;

#[\AllowDynamicProperties]
enum BlogDataEnum { /* … */ } // reported by ClassAttributesRule

#[\AllowDynamicProperties]
interface BlogDataInterface { /* … */ } // reported by ClassAttributesRule

#[\AllowDynamicProperties]
trait BlogDataTrait { /* … */ }

class Uses
{

	use BlogDataTrait;

}
