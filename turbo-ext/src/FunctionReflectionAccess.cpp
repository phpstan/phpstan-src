/*
 * Native readers of the function reflection getters the call handlers ask
 * for on every function call — the classes stay PHP, only the values their
 * getters return from constructor-written slots are read in place:
 *
 * - NativeFunctionReflection::getName() / getVariants() /
 *   getNamedArgumentsVariants() / getThrowType() / getAsserts() (property
 *   slots), isBuiltin() (always true) and hasSideEffects() (the variants'
 *   return types are asked isVoid() natively, then the slot);
 * - FunctionVariant::getReturnType() / getParameters() of an
 *   ExtendedFunctionVariant (ExtendedFunctionVariant::getParameters() is
 *   parent::getParameters());
 * - ParameterReflection::isOptional() (pt_parameter_reflection_bool());
 * - Assertions::getAll().
 *
 * Same contract as ReflectionAccess.cpp: only an object of exactly the
 * twin's class entry (resolved through the class map without autoloading —
 * an object of an undeclared class cannot exist) takes the slot, its offsets
 * resolved once per class entry per request; every other object, and a slot
 * never initialized, calls the method, so every error stays the twin's.
 */

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "ParameterValues.h"

namespace {

/* {{{ slot tables */

enum : uint32_t
{
	PT_FRA_NFR_NAME = 0,
	PT_FRA_NFR_VARIANTS,
	PT_FRA_NFR_NAMED_ARGUMENTS_VARIANTS,
	PT_FRA_NFR_THROW_TYPE,
	PT_FRA_NFR_HAS_SIDE_EFFECTS,
	PT_FRA_NFR_ASSERTIONS,
	PT_FRA_NFR_SLOT_COUNT,
};

enum : uint32_t
{
	PT_FRA_EFV_RETURN_TYPE = 0,
	PT_FRA_EFV_PARAMETERS,
	PT_FRA_EFV_SLOT_COUNT,
};

/* the instance-property offsets of one class entry, resolved once per
 * request; ce NULL = not resolvable (every call takes the method) */
struct ClassSlots
{
	zend_class_entry *ce;
	uint32_t generation;
	bool usable;
	uint32_t offsets[PT_FRA_NFR_SLOT_COUNT];
};

ClassSlots pt_fra_native_function_reflection;
ClassSlots pt_fra_extended_function_variant;
ClassSlots pt_fra_assertions;
ClassSlots pt_fra_memoizing_reflection_provider;
ClassSlots pt_fra_better_reflection_provider;
ClassSlots pt_fra_name;
ClassSlots pt_fra_fully_qualified_name;

/* the slots of an object of exactly the class-map class, NULL for any other
 * object (or when the class does not declare every named property) */
const ClassSlots *slotsOf(ClassSlots &cache, int classIdx, zend_object *object, const char *const *names, uint32_t count)
{
	if (EXPECTED(cache.ce == object->ce && cache.generation == pt_engine_generation)) return cache.usable ? &cache : NULL;
	zend_class_entry *ce = pt_class_loaded(classIdx);
	if (ce == NULL) {
		/* an undeclared class has no instances; a class-map failure leaves
		 * its exception pending for the caller's method call to meet */
		return NULL;
	}
	if (object->ce != ce) return NULL;
	cache.ce = ce;
	cache.generation = pt_engine_generation;
	cache.usable = true;
	for (uint32_t i = 0; i < count; i++) {
		int32_t offset = pt_instance_prop_offset(ce, names[i], strlen(names[i]));
		if (UNEXPECTED(offset < 0)) {
			cache.usable = false;
			return NULL;
		}
		cache.offsets[i] = (uint32_t) offset;
	}
	return &cache;
}

const char *const pt_fra_nfr_names[PT_FRA_NFR_SLOT_COUNT] = { "name", "variants", "namedArgumentsVariants", "throwType", "hasSideEffects", "assertions" };
const char *const pt_fra_efv_names[PT_FRA_EFV_SLOT_COUNT] = { "returnType", "parameters" };
const char *const pt_fra_assertions_names[1] = { "asserts" };
const char *const pt_fra_memoizing_names[1] = { "provider" };
const char *const pt_fra_better_names[2] = { "resolvedFunctionNames", "functionReflections" };
const char *const pt_fra_name_names[1] = { "name" };

inline const ClassSlots *nativeFunctionReflectionSlots(zval *reflection)
{
	return slotsOf(pt_fra_native_function_reflection, PT_CLASS_NATIVE_FUNCTION_REFLECTION, Z_OBJ_P(reflection), pt_fra_nfr_names, PT_FRA_NFR_SLOT_COUNT);
}

/* the initialized slot at an offset, NULL when never written */
inline zval *initializedSlot(zval *object, const ClassSlots *slots, uint32_t index)
{
	zval *value = OBJ_PROP(Z_OBJ_P(object), slots->offsets[index]);
	return EXPECTED(Z_TYPE_P(value) != IS_UNDEF) ? value : NULL;
}

/* $object->method() through the engine, the result kept in hold; NULL =
 * pending exception */
zend_never_inline zval *callGetter(zval *object, const char *lcname, size_t len, const char *name, zv::Val &hold)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return NULL;
	}
	hold = pt_type_call(Z_OBJ_P(object), lcname, len, 0, NULL);
	return hold.isUndef() ? NULL : hold.raw();
}

/* a NativeFunctionReflection getter: the slot, or the method */
zval *nativeFunctionReflectionRead(zval *reflection, uint32_t index, const char *lcname, size_t len, const char *name, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(reflection) == IS_OBJECT)) {
		const ClassSlots *slots = nativeFunctionReflectionSlots(reflection);
		if (EXPECTED(slots != NULL)) {
			zval *value = initializedSlot(reflection, slots, index);
			if (EXPECTED(value != NULL)) return value;
		}
	}
	return callGetter(reflection, lcname, len, name, hold);
}

/* }}} */

} // namespace

zval *pt_function_reflection_name(zval *reflection, zv::Val &hold)
{
	return nativeFunctionReflectionRead(reflection, PT_FRA_NFR_NAME, PT_LC("getname"), "getName", hold);
}

zval *pt_function_reflection_variants(zval *reflection, zv::Val &hold)
{
	return nativeFunctionReflectionRead(reflection, PT_FRA_NFR_VARIANTS, PT_LC("getvariants"), "getVariants", hold);
}

zval *pt_function_reflection_named_arguments_variants(zval *reflection, zv::Val &hold)
{
	return nativeFunctionReflectionRead(reflection, PT_FRA_NFR_NAMED_ARGUMENTS_VARIANTS, PT_LC("getnamedargumentsvariants"), "getNamedArgumentsVariants", hold);
}

zval *pt_function_reflection_throw_type(zval *reflection, zv::Val &hold)
{
	return nativeFunctionReflectionRead(reflection, PT_FRA_NFR_THROW_TYPE, PT_LC("getthrowtype"), "getThrowType", hold);
}

zval *pt_function_reflection_asserts(zval *reflection, zv::Val &hold)
{
	return nativeFunctionReflectionRead(reflection, PT_FRA_NFR_ASSERTIONS, PT_LC("getasserts"), "getAsserts", hold);
}

bool pt_function_reflection_is_builtin(zval *reflection, bool &out)
{
	if (EXPECTED(Z_TYPE_P(reflection) == IS_OBJECT) && nativeFunctionReflectionSlots(reflection) != NULL) {
		/* NativeFunctionReflection::isBuiltin(): return true; */
		out = true;
		return true;
	}
	zv::Val hold;
	zval *value = callGetter(reflection, PT_LC("isbuiltin"), "isBuiltin", hold);
	if (UNEXPECTED(value == NULL)) return false;
	out = zend_is_true(value);
	return true;
}

zval *pt_function_reflection_has_side_effects(zval *reflection, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(reflection) == IS_OBJECT)) {
		const ClassSlots *slots = nativeFunctionReflectionSlots(reflection);
		zval *variants = slots != NULL ? initializedSlot(reflection, slots, PT_FRA_NFR_VARIANTS) : NULL;
		zval *hasSideEffects = slots != NULL ? initializedSlot(reflection, slots, PT_FRA_NFR_HAS_SIDE_EFFECTS) : NULL;
		if (EXPECTED(variants != NULL && hasSideEffects != NULL && Z_TYPE_P(variants) == IS_ARRAY)) {
			/* if ($this->isVoid()) return TrinaryLogic::createYes(); — isVoid():
			 * every variant's getReturnType()->isVoid()->yes() */
			bool isVoid = true;
			zv::Val variantsHold = zv::Val::copyOf(zv::Ref(variants));
			for (zv::ArrayEntry entry : zv::ArrRef(variantsHold.raw())) {
				zval *variant = entry.value().deref().raw();
				zv::Val returnTypeHold;
				zval *returnType = pt_parameters_acceptor_return_type(variant, returnTypeHold);
				if (UNEXPECTED(returnType == NULL)) return NULL;
				if (UNEXPECTED(Z_TYPE_P(returnType) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function isVoid() on %s", zend_zval_value_name(returnType));
					return NULL;
				}
				zend_long voidness = pt_type_op_trinary(Z_OBJ_P(returnType), PT_OP_IS_VOID, 0, NULL);
				if (UNEXPECTED(voidness < 0)) return NULL;
				if (voidness != PT_TRI_YES) {
					isVoid = false;
					break;
				}
			}
			if (isVoid) return pt_trinary_singleton(PT_TRI_YES);
			return hasSideEffects;
		}
	}
	return callGetter(reflection, PT_LC("hassideeffects"), "hasSideEffects", hold);
}

zval *pt_parameters_acceptor_return_type(zval *acceptor, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(acceptor) == IS_OBJECT)) {
		const ClassSlots *slots = slotsOf(pt_fra_extended_function_variant, PT_CLASS_EXTENDED_FUNCTION_VARIANT, Z_OBJ_P(acceptor), pt_fra_efv_names, PT_FRA_EFV_SLOT_COUNT);
		if (EXPECTED(slots != NULL)) {
			zval *value = initializedSlot(acceptor, slots, PT_FRA_EFV_RETURN_TYPE);
			if (EXPECTED(value != NULL)) return value;
		}
	}
	return callGetter(acceptor, PT_LC("getreturntype"), "getReturnType", hold);
}

zval *pt_parameters_acceptor_parameters(zval *acceptor, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(acceptor) == IS_OBJECT)) {
		const ClassSlots *slots = slotsOf(pt_fra_extended_function_variant, PT_CLASS_EXTENDED_FUNCTION_VARIANT, Z_OBJ_P(acceptor), pt_fra_efv_names, PT_FRA_EFV_SLOT_COUNT);
		if (EXPECTED(slots != NULL)) {
			zval *value = initializedSlot(acceptor, slots, PT_FRA_EFV_PARAMETERS);
			if (EXPECTED(value != NULL)) return value;
		}
	}
	return callGetter(acceptor, PT_LC("getparameters"), "getParameters", hold);
}

bool pt_parameter_reflection_is_optional(zval *parameter, bool &out)
{
	return pt_parameter_reflection_bool(parameter, PT_PR_IS_OPTIONAL, out);
}

zval *pt_assertions_all(zval *assertions, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(assertions) == IS_OBJECT)) {
		const ClassSlots *slots = slotsOf(pt_fra_assertions, PT_CLASS_ASSERTIONS, Z_OBJ_P(assertions), pt_fra_assertions_names, 1);
		if (EXPECTED(slots != NULL)) {
			zval *value = initializedSlot(assertions, slots, 0);
			if (EXPECTED(value != NULL)) return value;
		}
	}
	return callGetter(assertions, PT_LC("getall"), "getAll", hold);
}

/* {{{ MemoizingReflectionProvider / BetterReflectionProvider function memo */

namespace {

/* the BetterReflectionProvider behind exactly a MemoizingReflectionProvider,
 * NULL for anything else */
zval *betterReflectionProviderOf(zval *provider, const ClassSlots *&slots)
{
	if (UNEXPECTED(Z_TYPE_P(provider) != IS_OBJECT)) return NULL;
	const ClassSlots *memoizing = slotsOf(pt_fra_memoizing_reflection_provider, PT_CLASS_MEMOIZING_REFLECTION_PROVIDER, Z_OBJ_P(provider), pt_fra_memoizing_names, 1);
	if (memoizing == NULL) return NULL;
	zval *inner = initializedSlot(provider, memoizing, 0);
	if (inner == NULL || Z_TYPE_P(inner) != IS_OBJECT) return NULL;
	slots = slotsOf(pt_fra_better_reflection_provider, PT_CLASS_BETTER_REFLECTION_PROVIDER, Z_OBJ_P(inner), pt_fra_better_names, 2);
	return slots != NULL ? inner : NULL;
}

/* the `name` of exactly a php-parser Name or Name\FullyQualified (whose
 * toLowerString() / __toString() / isFullyQualified() the resolution
 * reads), NULL for anything else */
zend_string *plainName(zval *nameNode, bool &fullyQualified)
{
	if (UNEXPECTED(Z_TYPE_P(nameNode) != IS_OBJECT)) return NULL;
	zend_object *object = Z_OBJ_P(nameNode);
	const ClassSlots *slots = slotsOf(pt_fra_name, PT_CLASS_NAME, object, pt_fra_name_names, 1);
	fullyQualified = false;
	if (slots == NULL) {
		slots = slotsOf(pt_fra_fully_qualified_name, PT_CLASS_FULLY_QUALIFIED, object, pt_fra_name_names, 1);
		if (slots == NULL) return NULL;
		fullyQualified = true;
	}
	zval *name = initializedSlot(nameNode, slots, 0);
	if (name == NULL) return NULL;
	ZVAL_DEREF(name);
	return Z_TYPE_P(name) == IS_STRING ? Z_STR_P(name) : NULL;
}

enum MemoAnswer
{
	PT_FRA_MEMO_MISS,
	PT_FRA_MEMO_UNRESOLVED,
	PT_FRA_MEMO_RESOLVED,
};

/* BetterReflectionProvider::resolveFunctionName() on its memo:
 * `exit`/`die` answer themselves, anything else the entry of
 * nameResolutionCacheKey() when memoized; resolved borrowed from the memo
 * or owned in hold; PT_FRA_MEMO_MISS also when the reader cannot tell
 * (the caller then calls the method); false = pending exception */
[[nodiscard]] bool memoizedFunctionName(zval *better, const ClassSlots *slots, zval *nameNode, zval *namespaceAnswerer, MemoAnswer &answer, zend_string *&resolved, zv::Str &hold)
{
	answer = PT_FRA_MEMO_MISS;
	bool fullyQualified;
	zend_string *name = plainName(nameNode, fullyQualified);
	if (name == NULL) return true;
	if (zend_string_equals_literal_ci(name, "exit") || zend_string_equals_literal_ci(name, "die")) {
		hold = zv::Str::adopt(zend_string_tolower(name));
		resolved = hold.get();
		answer = PT_FRA_MEMO_RESOLVED;
		return true;
	}
	zval *memo = initializedSlot(better, slots, 0);
	if (memo == NULL || Z_TYPE_P(memo) != IS_ARRAY) return true;

	zv::Val ns = zv::Val::null();
	if (namespaceAnswerer != NULL && Z_TYPE_P(namespaceAnswerer) != IS_NULL) {
		if (Z_TYPE_P(namespaceAnswerer) != IS_OBJECT) return true;
		ns = pt_mutating_scope_get_namespace(Z_OBJ_P(namespaceAnswerer));
		if (UNEXPECTED(ns.isUndef())) return false;
		if (UNEXPECTED(!ns.isNull() && !ns.ref().isString())) return true;
	}
	zend_string *nsString = ns.isNull() ? ZSTR_EMPTY_ALLOC() : Z_STR_P(ns.raw());
	size_t length = ZSTR_LEN(nsString) + 2 + (fullyQualified ? 1 : 0) + ZSTR_LEN(name);
	zend_string *key = zend_string_alloc(length, 0);
	char *cursor = ZSTR_VAL(key);
	memcpy(cursor, ZSTR_VAL(nsString), ZSTR_LEN(nsString));
	cursor += ZSTR_LEN(nsString);
	*cursor++ = ':';
	*cursor++ = ':';
	if (fullyQualified) *cursor++ = '\\';
	memcpy(cursor, ZSTR_VAL(name), ZSTR_LEN(name));
	cursor += ZSTR_LEN(name);
	*cursor = '\0';
	zval *found = zend_symtable_find(Z_ARRVAL_P(memo), key);
	zend_string_release(key);
	if (found == NULL) return true;
	ZVAL_DEREF(found);
	if (Z_TYPE_P(found) == IS_FALSE) {
		answer = PT_FRA_MEMO_UNRESOLVED;
		return true;
	}
	if (Z_TYPE_P(found) != IS_STRING) return true;
	resolved = Z_STR_P(found);
	answer = PT_FRA_MEMO_RESOLVED;
	return true;
}

/* $provider->method($nameNode, $namespaceAnswerer) through the engine */
zv::Val callProvider(zval *provider, const char *lcname, size_t len, const char *name, zval *nameNode, zval *namespaceAnswerer)
{
	if (UNEXPECTED(Z_TYPE_P(provider) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(provider));
		return zv::Val();
	}
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{nameNode, namespaceAnswerer != NULL ? namespaceAnswerer : &null};
	return pt_type_call(Z_OBJ_P(provider), lcname, len, 2, argv);
}

} // namespace

bool pt_reflection_provider_has_function(zval *provider, zval *nameNode, zval *namespaceAnswerer, bool &out)
{
	const ClassSlots *slots = NULL;
	zval *better = betterReflectionProviderOf(provider, slots);
	if (EXPECTED(better != NULL)) {
		MemoAnswer answer;
		zend_string *resolved = NULL;
		zv::Str hold;
		if (UNEXPECTED(!memoizedFunctionName(better, slots, nameNode, namespaceAnswerer, answer, resolved, hold))) return false;
		if (EXPECTED(answer != PT_FRA_MEMO_MISS)) {
			/* return $this->resolveFunctionName($nameNode, $namespaceAnswerer) !== null; */
			out = answer == PT_FRA_MEMO_RESOLVED;
			return true;
		}
	}
	zv::Val value = callProvider(provider, PT_LC("hasfunction"), "hasFunction", nameNode, namespaceAnswerer);
	if (UNEXPECTED(value.isUndef())) return false;
	out = zend_is_true(value.raw());
	return true;
}

zv::Val pt_reflection_provider_get_function(zval *provider, zval *nameNode, zval *namespaceAnswerer)
{
	const ClassSlots *slots = NULL;
	zval *better = betterReflectionProviderOf(provider, slots);
	if (EXPECTED(better != NULL)) {
		MemoAnswer answer;
		zend_string *resolved = NULL;
		zv::Str hold;
		if (UNEXPECTED(!memoizedFunctionName(better, slots, nameNode, namespaceAnswerer, answer, resolved, hold))) return zv::Val();
		zval *reflections = answer == PT_FRA_MEMO_RESOLVED ? initializedSlot(better, slots, 1) : NULL;
		if (reflections != NULL && Z_TYPE_P(reflections) == IS_ARRAY) {
			/* isset($this->functionReflections[strtolower($functionName)]) */
			zend_string *lower = zend_string_tolower(resolved);
			zval *found = zend_symtable_find(Z_ARRVAL_P(reflections), lower);
			zend_string_release(lower);
			if (found != NULL) {
				ZVAL_DEREF(found);
				if (EXPECTED(Z_TYPE_P(found) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(found));
			}
		}
	}
	return callProvider(provider, PT_LC("getfunction"), "getFunction", nameNode, namespaceAnswerer);
}

/* }}} */
