/*
 * Activation of the shadowing classes — Runtime::activateShadowing().
 *
 * Every class shadowing a PHP implementation is recorded at module startup
 * as a reg::ShadowPlan (reg::Class::shadow()) and declared here, at run
 * time, under the PHP twin's real name: PHPStan\TrinaryLogic *is* the
 * native class, no stub shell in between. That is only possible with
 * linked user classes, not internal ones — internal classes are registered
 * at module startup, before any userland parent or interface exists, and
 * PHPStan's classes implement userland interfaces (PHPStan\Type\Type) and
 * extend each other. So each plan becomes a zend_class_entry of type
 * ZEND_USER_CLASS built the way the compiler builds one (arena-allocated,
 * zend_initialize_class_data), holding internal functions — the engine
 * already supports those in user classes, every user class extending an
 * internal one inherits them — and is linked through zend_do_link_class(),
 * which resolves the parent and interfaces through the autoloader and runs
 * the same inheritance and signature checks a PHP declaration gets.
 *
 * The version gate stays in PHP (TurboExtensionEnabler): it calls
 * activateShadowing() after the Composer autoloader is registered and
 * before any of the twins could have been autoloaded; a mismatched
 * extension never gets the call and the PHP implementations load as if no
 * extension were present. The twin's source file is recorded as the class's
 * file, so reflection (and PHPStan's own AutoloadSourceLocator) keeps
 * reading the PHP declaration — attributes, PHPDocs and signatures included.
 *
 * The differential tests pass a name prefix ("PHPStanTurbo\") to declare the
 * native classes next to the PHP twins in one process.
 */

#include "reg.h"

#include "zend_inheritance.h"
#include "zend_observer.h"

#include <cstring>
#include <string>
#include <vector>

extern "C" {
extern zend_module_entry phpstan_turbo_module_entry;
}

static std::vector<reg::ShadowPlan> &pt_shadow_plans()
{
	static std::vector<reg::ShadowPlan> plans;
	return plans;
}

static bool pt_shadow_active = false;

void pt_shadow_plan_add(reg::ShadowPlan &&plan)
{
	pt_shadow_plans().push_back(std::move(plan));
}

bool pt_shadow_is_active()
{
	return pt_shadow_active;
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

static const char *pt_short_name(const char *fqcn)
{
	const char *slash = strrchr(fqcn, '\\');
	return slash != NULL ? slash + 1 : fqcn;
}

/* the declared name: the twin's real name, or the prefixed short name */
static std::string pt_shadow_declared_name(const char *realName, zend_string *prefix)
{
	if (prefix == NULL) return realName;
	return std::string(ZSTR_VAL(prefix), ZSTR_LEN(prefix)) + pt_short_name(realName);
}

static reg::ShadowPlan *pt_shadow_plan_by_name(const char *realName)
{
	for (reg::ShadowPlan &plan : pt_shadow_plans()) {
		if (strcmp(plan.name, realName) == 0) return &plan;
	}
	return NULL;
}

static bool pt_shadow_materialize(reg::ShadowPlan &plan, HashTable *twinFiles, zend_string *prefix);

/* whether the plan declares a method of that (lowercase) name itself —
 * its own or one of a trait registrar; case-insensitive like the engine's
 * function table */
static bool pt_shadow_plan_declares(const reg::ShadowPlan &plan, const char *lcname)
{
	for (const zend_function_entry &entry : plan.entries) {
		if (entry.fname != NULL && strcasecmp(entry.fname, lcname) == 0) return true;
	}
	return false;
}

/* declares one plan; a parent that is itself a plan is declared first */
static bool pt_shadow_materialize(reg::ShadowPlan &plan, HashTable *twinFiles, zend_string *prefix)
{
	if (plan.ce != NULL) return true;

	std::string parentDeclared;
	if (plan.parentName != NULL) {
		reg::ShadowPlan *parentPlan = pt_shadow_plan_by_name(plan.parentName);
		if (parentPlan != NULL && !pt_shadow_materialize(*parentPlan, twinFiles, prefix)) return false;
		parentDeclared = parentPlan != NULL ? pt_shadow_declared_name(plan.parentName, prefix) : std::string(plan.parentName);
	}

	std::string declared = pt_shadow_declared_name(plan.name, prefix);

	zend_class_entry *ce = (zend_class_entry *) zend_arena_alloc(&CG(arena), sizeof(zend_class_entry));
	memset(ce, 0, sizeof(zend_class_entry));
	ce->type = ZEND_USER_CLASS;
	ce->name = zend_new_interned_string(zend_string_init(declared.c_str(), declared.size(), 0));
	zend_initialize_class_data(ce, true);
	ce->ce_flags |= plan.flags;

	/* the twin's source file: reflection then reads the PHP declaration */
	zval *twinFile = twinFiles != NULL ? zend_hash_str_find(twinFiles, plan.name, strlen(plan.name)) : NULL;
	if (twinFile != NULL && Z_TYPE_P(twinFile) == IS_STRING) {
		ce->info.user.filename = zend_string_copy(Z_STR_P(twinFile));
	} else {
		ce->info.user.filename = ZSTR_EMPTY_ALLOC();
	}
	ce->info.user.line_start = 0;
	ce->info.user.line_end = 0;

	zend_string *lcParentName = NULL;
	if (plan.parentName != NULL) {
		ce->parent_name = zend_new_interned_string(zend_string_init(parentDeclared.c_str(), parentDeclared.size(), 0));
		lcParentName = zend_string_tolower(ce->parent_name);
	}

	if (!plan.interfaces.empty()) {
		ce->num_interfaces = (uint32_t) plan.interfaces.size();
		ce->interface_names = (zend_class_name *) emalloc(sizeof(zend_class_name) * ce->num_interfaces);
		for (uint32_t i = 0; i < ce->num_interfaces; i++) {
			const char *interfaceName = plan.interfaces[i];
			ce->interface_names[i].name = zend_new_interned_string(zend_string_init(interfaceName, strlen(interfaceName), 0));
			ce->interface_names[i].lc_name = zend_string_tolower(ce->interface_names[i].name);
		}
	}

	/* the methods: internal functions in a user class, exactly what the
	 * engine produces when a user class inherits an internal method; the
	 * module is set the way module startup sets it */
	zend_module_entry *previousModule = EG(current_module);
	EG(current_module) = &phpstan_turbo_module_entry;
	zend_result registered = zend_register_functions(ce, plan.entries.data(), &ce->function_table, MODULE_PERSISTENT);
	EG(current_module) = previousModule;
	if (registered != SUCCESS) {
		zend_throw_error(NULL, "phpstan_turbo: registering the methods of %s failed", declared.c_str());
		return false;
	}

	reg::declareMembers(ce, plan.properties, plan.constants);

	zend_string *lcName = zend_string_tolower(ce->name);
	if (zend_hash_add_ptr(EG(class_table), lcName, ce) == NULL) {
		zend_throw_error(NULL, "phpstan_turbo: cannot shadow %s — the class is already declared (activateShadowing() must run before it is autoloaded)", declared.c_str());
		zend_string_release(lcName);
		return false;
	}

	zend_class_entry *linked = zend_do_link_class(ce, lcParentName, lcName);
	if (lcParentName != NULL) {
		zend_string_release(lcParentName);
	}
	if (linked == NULL) {
		zend_hash_del(EG(class_table), lcName);
		zend_string_release(lcName);
		if (!EG(exception)) {
			zend_throw_error(NULL, "phpstan_turbo: linking %s failed", declared.c_str());
		}
		return false;
	}
	zend_observer_class_linked_notify(linked, lcName);
	zend_string_release(lcName);

	plan.ce = linked;
	if (plan.out != NULL) {
		*plan.out = linked;
	}

	/* the direct entries (TypeOps.h): the plan's own with this class as
	 * their scope, and for every method the plan does not declare itself
	 * the parent plan's entry with the parent's scope — the inherited
	 * internal method keeps its declaring scope in the engine too */
	pt_type_ops *ops = (pt_type_ops *) pecalloc(1, sizeof(pt_type_ops), 1);
	reg::ShadowPlan *parentPlan = plan.parentName != NULL ? pt_shadow_plan_by_name(plan.parentName) : NULL;
	for (int op = 0; op < PT_OP_COUNT; op++) {
		if (plan.opFns[op] != NULL) {
			ops->entries[op].fn = plan.opFns[op];
			ops->entries[op].scope = linked;
		} else if (parentPlan != NULL && parentPlan->ops != NULL && parentPlan->ops->entries[op].fn != NULL && !pt_shadow_plan_declares(plan, pt_type_op_infos[op].lcname)) {
			ops->entries[op] = parentPlan->ops->entries[op];
		}
	}
	plan.ops = ops;
	pt_type_ops_attach(linked, ops);
	return true;
}

/*
 * Declares every shadowing class. twinFiles maps each real class name to
 * its PHP twin's source file (may be empty); prefix, when given, declares
 * the classes as "<prefix><short name>" instead of the real names. Returns
 * false with an exception pending when a class could not be declared.
 */
[[nodiscard]] bool pt_shadow_activate(HashTable *twinFiles, zend_string *prefix)
{
	if (pt_shadow_active) {
		zend_throw_error(NULL, "phpstan_turbo: the shadowing classes are already active");
		return false;
	}
	for (reg::ShadowPlan &plan : pt_shadow_plans()) {
		/* an incomplete port is declared only next to its twin, under the
		 * prefix, for the differential tests (reg::Class::shadowDifferentialOnly()) */
		if (plan.differentialOnly && prefix == NULL) continue;
		if (!pt_shadow_materialize(plan, twinFiles, prefix)) return false;
	}
	/* the visitor ports' class entries are set now — the NodeTraverser's
	 * index of natively dispatched visitors keys on them */
	pt_native_visitor_index_reset();
	pt_shadow_active = true;
	return true;
}
