/*
 * The shared core's own state and the entry points the version-specific
 * library (main.cpp, Abi.cpp, Shadow.cpp, TrustedTypes.cpp) calls into.
 * Everything here is version-neutral: the core is compiled once per
 * platform and loaded by the thin per-PHP-version extension, and it never
 * references a symbol of that extension — what differs between minors is
 * read through pt_abi (abi.h), which the extension fills in at startup.
 */

#include "support.h"
#include "reg.h"
#include "Engine.h"

#include <vector>

PT_CORE_API pt_abi_globals pt_abi;

/* the PT_MINIT_REGISTRATION() functions of every file, in name order; a
 * constant-initialized pointer, so it is null before any of the file-static
 * registrations construct */
static pt_minit_registration *pt_minit_registrations = nullptr;

pt_minit_registration::pt_minit_registration(const char *name, void (*run)()) noexcept
	: name(name), run(run), next(nullptr)
{
	pt_minit_registration **slot = &pt_minit_registrations;
	while (*slot != nullptr && strcmp((*slot)->name, name) < 0) {
		slot = &(*slot)->next;
	}
	next = *slot;
	*slot = this;
}

PT_CORE_API void pt_minit_registrations_run()
{
	for (const pt_minit_registration *registration = pt_minit_registrations; registration != nullptr; registration = registration->next) {
		registration->run();
	}
}

/* the shadowing plans recorded at module startup (reg::Class::shadow()),
 * declared by Runtime::activateShadowing() (Shadow.cpp) */
PT_CORE_API std::vector<reg::ShadowPlan> &pt_shadow_plans()
{
	static std::vector<reg::ShadowPlan> plans;
	return plans;
}

void pt_shadow_plan_add(reg::ShadowPlan &&plan)
{
	pt_shadow_plans().push_back(std::move(plan));
}

bool pt_shadow_instanceof(zend_class_entry *ce, zend_class_entry *nativeCe, const char *realName, size_t realNameLen)
{
	if (EXPECTED(nativeCe != NULL && instanceof_function(ce, nativeCe))) return true;
	if (nativeCe == NULL || zend_string_equals_cstr(nativeCe->name, realName, realNameLen)) return false;
	zend_string *name = zend_string_init(realName, realNameLen, 0);
	zend_class_entry *twin = zend_lookup_class_ex(name, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
	zend_string_release(name);
	return twin != NULL && instanceof_function(ce, twin);
}

PT_CORE_API void pt_core_rinit()
{
	pt_support_rinit();
	pt_node_traverser_rinit();
	pt_scope_ops_rinit();
	pt_type_combinator_cache_rinit();
	pt_is_super_type_of_result_rinit();
	pt_accepts_result_rinit();
	pt_integer_range_type_rinit();
	pt_object_type_rinit();
	pt_static_type_factory_rinit();
	pt_scope_access_rinit();
	pt_reflection_access_rinit();
	pt_mutating_scope_rinit();
	pt_variable_flow_rinit();
	pt_engine_rinit();
}

PT_CORE_API void pt_core_rshutdown()
{
	pt_scope_ops_rshutdown();
	pt_node_traverser_rshutdown();
	pt_type_combinator_cache_rshutdown();
	pt_is_super_type_of_result_rshutdown();
	pt_accepts_result_rshutdown();
	pt_support_rshutdown();
	pt_object_type_rshutdown();
	pt_static_type_factory_rshutdown();
	pt_engine_rshutdown();
}

PT_CORE_API void pt_core_mshutdown()
{
	pt_arena_mshutdown();
}
