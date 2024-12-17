<?php // lint >= 8.2

namespace Bug12281;

#[\AllowDynamicProperties]
readonly class BlogData { /* … */ }

/** @readonly  */
#[\AllowDynamicProperties]
class BlogDataPhpdoc { /* … */ }

#[\AllowDynamicProperties]
enum BlogDataEnum { /* … */ }

#[\AllowDynamicProperties]
interface BlogDataInterface { /* … */ }
