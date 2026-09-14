/*
 * Fluent, zero-cost class registration — PHP-CPP's extension.add() look
 * without its call-time cost. The builder assembles the same
 * zend_internal_arg_info / zend_function_entry structures the PHP_METHOD +
 * ZEND_BEGIN_ARG_INFO_EX + PHP_ME macro triple produced, and hands the
 * engine the raw handler pointers directly — no trampoline, no Php::Value
 * boxing, byte-identical dispatch. Handlers are plain functions,
 * non-capturing lambdas with the (INTERNAL_FUNCTION_PARAMETERS) signature,
 * or generated from a handle member and its parameter kinds
 * (Class::method<M, K...>()), so each method is declared exactly once: name,
 * flags, signature and body together at the registration site.
 *
 * Two registration paths share the builder:
 * - register_() registers an internal class at module startup (extension-only
 *   classes such as PHPStanTurbo\Runtime).
 * - shadow() records a plan for a class that shadows a PHP implementation.
 *   Plans are materialised by Runtime::activateShadowing() (Shadow.cpp) as
 *   linked *user* classes carrying the PHP twin's real name — see README.md,
 *   "How it works".
 *
 * Everything here runs once at module startup; allocations are persistent
 * (the engine references the arginfo arrays for the process lifetime).
 */

#ifndef PHPSTANTURBO_REG_H
#define PHPSTANTURBO_REG_H

#include "support.h"
#include "TypeOps.h"

#include <cstring>
#include <initializer_list>
#include <tuple>
#include <type_traits>
#include <utility>
#include <vector>

/* {{{ handler glue shared by every registration site */

/* a zv::Val result into return_value; RETURN_THROWS on UNDEF (pending exception) */
#define PT_RETURN_VAL(expr) \
	do { \
		zv::Val pt_result__ = (expr); \
		if (UNEXPECTED(pt_result__.isUndef())) { \
			RETURN_THROWS(); \
		} \
		pt_result__.intoReturnValue(return_value); \
		return; \
	} while (0)

/* argument-count check for parameters the twin never reads */
#define PT_ARGS(min, max) \
	do { \
		if (UNEXPECTED(ZEND_NUM_ARGS() < (uint32_t) (min) || ZEND_NUM_ARGS() > (uint32_t) (max))) { \
			zend_wrong_parameters_count_error(min, max); \
			RETURN_THROWS(); \
		} \
	} while (0)

#define PT_RETURN_TRINARY(value) RETURN_COPY(pt_trinary_singleton(value))

/* a PT_TRI_* verdict into return_value; RETURN_THROWS when negative (pending exception) */
#define PT_RETURN_TRINARY_OR_THROW(expr) \
	do { \
		zend_long pt_value__ = (expr); \
		if (UNEXPECTED(pt_value__ < 0)) { \
			RETURN_THROWS(); \
		} \
		RETURN_COPY(pt_trinary_singleton(pt_value__)); \
	} while (0)

/* }}} */

/* {{{ zp: typed parameter parsing
 *
 * zp::parse<K...>(execute_data, dests...) is the ZEND_PARSE_PARAMETERS_START
 * ... END block of a glue function written once: each kind K names the
 * Z_PARAM_* macro its slot expands to, zp::Opt<K> puts Z_PARAM_OPTIONAL in
 * front of it (the destination keeps its initializer when the argument is
 * not passed), and the block is the engine's own macros — the code is the
 * hand-written block's. false = pending exception.
 */
namespace zp {

struct Obj { using type = zval *; };            /* Z_PARAM_OBJECT */
struct ObjOrNull { using type = zval *; };      /* Z_PARAM_OBJECT_OR_NULL */
struct Bool { using type = bool; };             /* Z_PARAM_BOOL */
struct Str { using type = zend_string *; };     /* Z_PARAM_STR */
struct StrOrNull { using type = zend_string *; }; /* Z_PARAM_STR_OR_NULL */
struct Arr { using type = zval *; };            /* Z_PARAM_ARRAY */
struct ArrOrNull { using type = zval *; };      /* Z_PARAM_ARRAY_OR_NULL */
struct Ht { using type = HashTable *; };        /* Z_PARAM_ARRAY_HT */
struct HtOrNull { using type = HashTable *; };  /* Z_PARAM_ARRAY_HT_OR_NULL */
struct Zval { using type = zval *; };           /* Z_PARAM_ZVAL */
struct Long { using type = zend_long; };        /* Z_PARAM_LONG */
struct Double { using type = double; };         /* Z_PARAM_DOUBLE */

/* a parameter after Z_PARAM_OPTIONAL */
template <typename K>
struct Opt : K
{
};

namespace detail {

template <typename K>
struct Base
{
	using type = K;
	static constexpr bool optional = false;
};

template <typename K>
struct Base<Opt<K>>
{
	using type = K;
	static constexpr bool optional = true;
};

template <typename K, typename Kind>
constexpr bool is = std::is_same_v<typename Base<K>::type, Kind>;


} // namespace detail

template <typename... K>
constexpr uint32_t required()
{
	return (0u + ... + (detail::Base<K>::optional ? 0u : 1u));
}

/* one Z_PARAM_* slot of a ZEND_PARSE_PARAMETERS block, chosen by the kind (the
 * blocks' required<K...>() counts are parenthesized: the template arguments'
 * commas would split the macro arguments) */
#define PT_ZP_SLOT(K, dest) \
	if constexpr (detail::Base<K>::optional) { \
		Z_PARAM_OPTIONAL \
	} \
	if constexpr (detail::is<K, Obj>) { \
		Z_PARAM_OBJECT(dest) \
	} else if constexpr (detail::is<K, ObjOrNull>) { \
		Z_PARAM_OBJECT_OR_NULL(dest) \
	} else if constexpr (detail::is<K, Bool>) { \
		Z_PARAM_BOOL(dest) \
	} else if constexpr (detail::is<K, Str>) { \
		Z_PARAM_STR(dest) \
	} else if constexpr (detail::is<K, StrOrNull>) { \
		Z_PARAM_STR_OR_NULL(dest) \
	} else if constexpr (detail::is<K, Arr>) { \
		Z_PARAM_ARRAY(dest) \
	} else if constexpr (detail::is<K, ArrOrNull>) { \
		Z_PARAM_ARRAY_OR_NULL(dest) \
	} else if constexpr (detail::is<K, Ht>) { \
		Z_PARAM_ARRAY_HT(dest) \
	} else if constexpr (detail::is<K, HtOrNull>) { \
		Z_PARAM_ARRAY_HT_OR_NULL(dest) \
	} else if constexpr (detail::is<K, Zval>) { \
		Z_PARAM_ZVAL(dest) \
	} else if constexpr (detail::is<K, Long>) { \
		Z_PARAM_LONG(dest) \
	} else { \
		static_assert(detail::is<K, Double>, "not a zp kind"); \
		Z_PARAM_DOUBLE(dest) \
	}

template <typename K1>
zend_always_inline bool parse(zend_execute_data *execute_data, typename K1::type &d1)
{
	ZEND_PARSE_PARAMETERS_START((required<K1>()), 1)
		PT_ZP_SLOT(K1, d1)
	ZEND_PARSE_PARAMETERS_END_EX(return false);
	return true;
}

template <typename K1, typename K2>
zend_always_inline bool parse(zend_execute_data *execute_data, typename K1::type &d1, typename K2::type &d2)
{
	ZEND_PARSE_PARAMETERS_START((required<K1, K2>()), 2)
		PT_ZP_SLOT(K1, d1)
		PT_ZP_SLOT(K2, d2)
	ZEND_PARSE_PARAMETERS_END_EX(return false);
	return true;
}

template <typename K1, typename K2, typename K3>
zend_always_inline bool parse(zend_execute_data *execute_data, typename K1::type &d1, typename K2::type &d2, typename K3::type &d3)
{
	ZEND_PARSE_PARAMETERS_START((required<K1, K2, K3>()), 3)
		PT_ZP_SLOT(K1, d1)
		PT_ZP_SLOT(K2, d2)
		PT_ZP_SLOT(K3, d3)
	ZEND_PARSE_PARAMETERS_END_EX(return false);
	return true;
}

template <typename K1, typename K2, typename K3, typename K4>
zend_always_inline bool parse(zend_execute_data *execute_data, typename K1::type &d1, typename K2::type &d2, typename K3::type &d3, typename K4::type &d4)
{
	ZEND_PARSE_PARAMETERS_START((required<K1, K2, K3, K4>()), 4)
		PT_ZP_SLOT(K1, d1)
		PT_ZP_SLOT(K2, d2)
		PT_ZP_SLOT(K3, d3)
		PT_ZP_SLOT(K4, d4)
	ZEND_PARSE_PARAMETERS_END_EX(return false);
	return true;
}

template <typename K1, typename K2, typename K3, typename K4, typename K5>
zend_always_inline bool parse(zend_execute_data *execute_data, typename K1::type &d1, typename K2::type &d2, typename K3::type &d3, typename K4::type &d4, typename K5::type &d5)
{
	ZEND_PARSE_PARAMETERS_START((required<K1, K2, K3, K4, K5>()), 5)
		PT_ZP_SLOT(K1, d1)
		PT_ZP_SLOT(K2, d2)
		PT_ZP_SLOT(K3, d3)
		PT_ZP_SLOT(K4, d4)
		PT_ZP_SLOT(K5, d5)
	ZEND_PARSE_PARAMETERS_END_EX(return false);
	return true;
}

template <typename K1, typename K2, typename K3, typename K4, typename K5, typename K6>
zend_always_inline bool parse(zend_execute_data *execute_data, typename K1::type &d1, typename K2::type &d2, typename K3::type &d3, typename K4::type &d4, typename K5::type &d5, typename K6::type &d6)
{
	ZEND_PARSE_PARAMETERS_START((required<K1, K2, K3, K4, K5, K6>()), 6)
		PT_ZP_SLOT(K1, d1)
		PT_ZP_SLOT(K2, d2)
		PT_ZP_SLOT(K3, d3)
		PT_ZP_SLOT(K4, d4)
		PT_ZP_SLOT(K5, d5)
		PT_ZP_SLOT(K6, d6)
	ZEND_PARSE_PARAMETERS_END_EX(return false);
	return true;
}

#undef PT_ZP_SLOT

} // namespace zp

/* }}} */

namespace reg {

constexpr uint32_t Public = ZEND_ACC_PUBLIC;
constexpr uint32_t Protected = ZEND_ACC_PROTECTED;
constexpr uint32_t Private = ZEND_ACC_PRIVATE;
constexpr uint32_t Static = ZEND_ACC_STATIC;
constexpr uint32_t PublicStatic = ZEND_ACC_PUBLIC | ZEND_ACC_STATIC;
constexpr uint32_t ProtectedStatic = ZEND_ACC_PROTECTED | ZEND_ACC_STATIC;

/* One parameter's metadata, mirroring what the ZEND_ARG_* macros encode. */
struct Arg
{
	const char *name;
	uint32_t typeMask;               /* MAY_BE_* mask incl. by-ref/variadic bits */
	const char *className = nullptr; /* persistent literal for object types */
	const char *defaultValue = nullptr; /* persistent literal of the default value's PHP source ("[]"), needed for named-argument skipping and reflection */
};

/* an optional parameter with its default value's PHP source (e.g. "[]") —
 * without it the engine refuses to skip the parameter via named arguments */
constexpr Arg withDefault(Arg arg, const char *defaultValue)
{
	arg.defaultValue = defaultValue;
	return arg;
}

namespace detail {

constexpr uint32_t flagBits(bool byRef, bool variadic)
{
	return _ZEND_ARG_INFO_FLAGS(byRef ? ZEND_SEND_BY_REF : ZEND_SEND_BY_VAL, variadic ? 1 : 0, 0);
}

constexpr uint32_t codeMask(zend_uchar code, bool nullable)
{
	uint32_t mask = code == _IS_BOOL ? MAY_BE_BOOL : (uint32_t) (1u << code);
	return mask | (nullable ? MAY_BE_NULL : 0);
}

} // namespace detail

/* an untyped parameter (ZEND_ARG_INFO) */
constexpr Arg any(const char *name, bool byRef = false)
{
	return { name, detail::flagBits(byRef, false), nullptr };
}

constexpr Arg longArg(const char *name, bool nullable = false)
{
	return { name, detail::codeMask(IS_LONG, nullable) | detail::flagBits(false, false), nullptr };
}

constexpr Arg doubleArg(const char *name)
{
	return { name, detail::codeMask(IS_DOUBLE, false) | detail::flagBits(false, false), nullptr };
}

constexpr Arg boolArg(const char *name, bool nullable = false)
{
	return { name, detail::codeMask(_IS_BOOL, nullable) | detail::flagBits(false, false), nullptr };
}

constexpr Arg stringArg(const char *name, bool nullable = false)
{
	return { name, detail::codeMask(IS_STRING, nullable) | detail::flagBits(false, false), nullptr };
}

constexpr Arg arrayArg(const char *name, bool nullable = false)
{
	return { name, detail::codeMask(IS_ARRAY, nullable) | detail::flagBits(false, false), nullptr };
}

constexpr Arg callableArg(const char *name, bool nullable = false)
{
	return { name, MAY_BE_CALLABLE | (nullable ? MAY_BE_NULL : 0) | detail::flagBits(false, false), nullptr };
}

constexpr Arg objectArg(const char *name, bool nullable = false)
{
	return { name, detail::codeMask(IS_OBJECT, nullable) | detail::flagBits(false, false), nullptr };
}

/* object of a specific class; className must be a persistent literal */
constexpr Arg obj(const char *name, const char *className, bool nullable = false)
{
	return { name, _ZEND_TYPE_LITERAL_NAME_BIT | (nullable ? MAY_BE_NULL : 0) | detail::flagBits(false, false), className };
}

constexpr Arg variadicObj(const char *name, const char *className)
{
	return { name, _ZEND_TYPE_LITERAL_NAME_BIT | detail::flagBits(false, true), className };
}

/* a `mixed` parameter (ZEND_ARG_TYPE_INFO with IS_MIXED) */
constexpr Arg mixedArg(const char *name)
{
	return { name, MAY_BE_ANY | detail::flagBits(false, false), nullptr };
}

/* an `array &$x` parameter (ZEND_ARG_TYPE_INFO with IS_ARRAY, by reference) */
constexpr Arg arrayRefArg(const char *name)
{
	return { name, detail::codeMask(IS_ARRAY, false) | detail::flagBits(true, false), nullptr };
}

/* a `?Foo ...$x` nullable variadic of a specific class; className must be a
 * persistent literal */
constexpr Arg nullableVariadicObj(const char *name, const char *className)
{
	return { name, _ZEND_TYPE_LITERAL_NAME_BIT | MAY_BE_NULL | detail::flagBits(false, true), className };
}

/* a parameter or return type the way a generated signature spells it
 * (turbo-ext/src/generated): the MAY_BE_* mask, a persistent literal class
 * name ("Foo", "Foo|Bar", "self") or nullptr, by reference / variadic, the
 * PHP source of the default value or nullptr — the same bits the
 * descriptors above produce */
constexpr Arg typed(const char *name, uint32_t mask, const char *className = nullptr, bool byRef = false, bool variadic = false, const char *defaultValue = nullptr)
{
	return { name, mask | (className != nullptr ? _ZEND_TYPE_LITERAL_NAME_BIT : 0) | detail::flagBits(byRef, variadic), className, defaultValue };
}

/* a method's signature as generated from the PHP twin: name, ZEND_ACC_*
 * flags, the required-parameter count, the parameters' arginfo and the
 * declared return type (nullptr: none) */
struct Sig
{
	const char *name;
	uint32_t flags;
	uint32_t requiredArgs;
	const Arg *args;
	uint32_t argc;
	const Arg *returns;
};

enum class PropertyKind
{
	Long,
	Null,
	Bool,
	EmptyArray,
	Typed, /* a typed property with no default (UNDEF, IS_PROP_UNINIT); defaultValue carries the MAY_BE_* type mask, visibility the ZEND_ACC_* flags */
	TypedNull, /* a typed property defaulting to null (`private ?Foo $x = null`); defaultValue as for Typed */
	TypedEmptyArray, /* a typed property defaulting to [] (`private array $x = []`); defaultValue as for Typed */
	TypedClassUnion, /* a `private Foo|Bar $x` union-of-classes typed property with no default; className carries the `|`-separated names, defaultValue as for Typed */
	TypedBool, /* a `private bool $x = false` typed property with a bool default; defaultValue carries the default (0/1), the type is bool */
	TypedLong, /* a `private int $x = 0` typed property with an int default; defaultValue carries the default, the type is int */
	TypedFalse, /* a typed property defaulting to false (`private string|false|null $x = false`, `private Foo|false|null $x = false`); defaultValue carries the MAY_BE_* mask (the scalar members next to a class name), className as for Typed */
};

struct Property
{
	const char *name;
	PropertyKind kind;
	uint32_t visibility;
	zend_long defaultValue;
	const char *className = nullptr; /* persistent literal: the class of a class-typed property (Typed* kinds), combined with a MAY_BE_NULL bit in defaultValue for `?Foo` */
};

struct Constant
{
	const char *name;
	zend_long value;
	uint32_t flags; /* ZEND_ACC_PUBLIC / ZEND_ACC_PRIVATE */
	const char *stringValue = nullptr; /* persistent literal of a string constant (`private const X = '...'`); value is unused then */
	void (*buildValue)(zval *out) = nullptr; /* a non-int constant (an array): fills *out with a persistent, immutable value the engine references for the process lifetime */
};

/*
 * Everything a shadowing class needs to be declared at activation time: the
 * PHP twin's real name (the class is registered under it, or under
 * "<prefix><short name>" in the differential tests), its final flag, parent
 * and interfaces, and the members. The entries vector ends with the sentinel
 * zend_register_functions() expects. `out` receives the linked class entry.
 */
struct ShadowPlan
{
	const char *name;
	uint32_t flags;
	const char *parentName;
	std::vector<const char *> interfaces;
	std::vector<zend_function_entry> entries;
	std::vector<Property> properties;
	std::vector<Constant> constants;
	zend_class_entry **out;
	zend_class_entry *ce;
	/* the direct entries registered with op() / traitOp(), per pt_type_op_id
	 * (NULL = none); the linked table with the inherited entries is built
	 * at activation (Shadow.cpp) and kept here for the children */
	pt_type_op_fn opFns[PT_OP_COUNT];
	const pt_type_ops *ops;
	/* declared only by a prefixed activation (the differential tests): a
	 * class whose port is still incomplete — never under the twin's real
	 * name (reg::Class::shadowDifferentialOnly()) */
	bool differentialOnly;
};

/* declares the builder's properties and constants on a registered or
 * initialised class entry; works for internal and user classes alike since
 * the engine picks the allocation from ce->type */
inline void declareMembers(zend_class_entry *ce, const std::vector<Property> &properties, const std::vector<Constant> &constants)
{
	for (const Property &property : properties) {
		size_t len = strlen(property.name);
		switch (property.kind) {
			case PropertyKind::Long:
				zend_declare_property_long(ce, property.name, len, property.defaultValue, property.visibility);
				break;
			case PropertyKind::Null:
				zend_declare_property_null(ce, property.name, len, property.visibility);
				break;
			case PropertyKind::Bool:
				zend_declare_property_bool(ce, property.name, len, property.defaultValue, property.visibility);
				break;
			case PropertyKind::EmptyArray: {
				zval emptyArray;
				ZVAL_EMPTY_ARRAY(&emptyArray);
				zend_declare_property(ce, property.name, len, &emptyArray, property.visibility);
				break;
			}
			case PropertyKind::Typed:
			case PropertyKind::TypedNull:
			case PropertyKind::TypedEmptyArray:
			case PropertyKind::TypedClassUnion:
			case PropertyKind::TypedFalse: {
				bool persistent = ce->type == ZEND_INTERNAL_CLASS;
				zend_string *nameStr = zend_string_init(property.name, len, persistent);
				zval defaultValue;
				if (property.kind == PropertyKind::TypedNull) {
					ZVAL_NULL(&defaultValue);
				} else if (property.kind == PropertyKind::TypedEmptyArray) {
					ZVAL_EMPTY_ARRAY(&defaultValue);
				} else if (property.kind == PropertyKind::TypedFalse) {
					ZVAL_FALSE(&defaultValue);
				} else {
					ZVAL_UNDEF(&defaultValue);
				}
				zend_type type;
				if (property.kind == PropertyKind::TypedClassUnion) {
					/* a `Foo|Bar` union of class names, declared the way the
					 * compiler declares one: a type list of interned names
					 * with class-entry cache slots (released with the class
					 * by zend_type_release() — allocated with its
					 * persistence) */
					uint32_t count = 1;
					for (const char *p = property.className; (p = strchr(p, '|')) != NULL; p++) {
						count++;
					}
					zend_type_list *list = (zend_type_list *) pemalloc(ZEND_TYPE_LIST_SIZE(count), persistent);
					list->num_types = count;
					const char *start = property.className;
					for (uint32_t i = 0; i < count; i++) {
						const char *end = strchr(start, '|');
						size_t partLen = end != NULL ? (size_t) (end - start) : strlen(start);
						zend_string *className = zend_new_interned_string(zend_string_init(start, partLen, persistent));
						zend_alloc_ce_cache(className);
						list->types[i] = (zend_type) ZEND_TYPE_INIT_CLASS(className, 0, 0);
						start = end != NULL ? end + 1 : start;
					}
					type = (zend_type) ZEND_TYPE_INIT_UNION(list, (property.defaultValue & MAY_BE_NULL) != 0 ? MAY_BE_NULL : 0);
				} else if (property.className != NULL) {
					/* a class-typed property, declared the way the compiler
					 * declares one (zend_compile_single_typename): an interned
					 * name with a class-entry cache slot; the engine dups it
					 * for a persistent class. "self" is the declared class,
					 * as the compiler resolves it (the twin's `private static
					 * self $x`) */
					zend_string *className = strcmp(property.className, "self") == 0
						? zend_string_copy(ce->name)
						: zend_new_interned_string(zend_string_init(property.className, strlen(property.className), persistent));
					zend_alloc_ce_cache(className);
					/* the bits beyond MAY_BE_NULL in defaultValue are the scalar
					 * members of a `Foo|false|null` union */
					type = (zend_type) ZEND_TYPE_INIT_CLASS(className, (property.defaultValue & MAY_BE_NULL) != 0, (uint32_t) property.defaultValue & ~(uint32_t) MAY_BE_NULL);
				} else {
					type = (zend_type) ZEND_TYPE_INIT_MASK((uint32_t) property.defaultValue);
				}
				zend_declare_typed_property(ce, nameStr, &defaultValue, property.visibility, NULL, type);
				zend_string_release(nameStr);
				break;
			}
			case PropertyKind::TypedBool: {
				bool persistent = ce->type == ZEND_INTERNAL_CLASS;
				zend_string *nameStr = zend_string_init(property.name, len, persistent);
				zval defaultValue;
				ZVAL_BOOL(&defaultValue, property.defaultValue != 0);
				zend_type type = (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL);
				zend_declare_typed_property(ce, nameStr, &defaultValue, property.visibility, NULL, type);
				zend_string_release(nameStr);
				break;
			}
			case PropertyKind::TypedLong: {
				bool persistent = ce->type == ZEND_INTERNAL_CLASS;
				zend_string *nameStr = zend_string_init(property.name, len, persistent);
				zval defaultValue;
				ZVAL_LONG(&defaultValue, property.defaultValue);
				zend_type type = (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG);
				zend_declare_typed_property(ce, nameStr, &defaultValue, property.visibility, NULL, type);
				zend_string_release(nameStr);
				break;
			}
		}
	}
	for (const Constant &constant : constants) {
		if (constant.buildValue != nullptr) {
			zend_string *nameStr = zend_string_init_interned(constant.name, strlen(constant.name), ce->type == ZEND_INTERNAL_CLASS);
			zval value;
			constant.buildValue(&value);
			zend_declare_class_constant_ex(ce, nameStr, &value, (int) constant.flags, NULL);
			zend_string_release(nameStr);
			continue;
		}
		if (constant.flags == ZEND_ACC_PUBLIC && constant.stringValue == nullptr) {
			zend_declare_class_constant_long(ce, constant.name, strlen(constant.name), constant.value);
			continue;
		}
		zend_string *nameStr = zend_string_init_interned(constant.name, strlen(constant.name), ce->type == ZEND_INTERNAL_CLASS);
		zval value;
		if (constant.stringValue != nullptr) {
			/* interned like a compiled literal: a user class's constants are
			 * released with the class, an internal class's must persist */
			ZVAL_STR(&value, zend_string_init_interned(constant.stringValue, strlen(constant.stringValue), ce->type == ZEND_INTERNAL_CLASS));
		} else {
			ZVAL_LONG(&value, constant.value);
		}
		zend_declare_class_constant_ex(ce, nameStr, &value, (int) constant.flags, NULL);
		zend_string_release(nameStr);
	}
}

} // namespace reg

/* Shadow.cpp: the plan registry and Runtime::activateShadowing() */
void pt_shadow_plan_add(reg::ShadowPlan &&plan);

namespace reg {

namespace detail {

/* a bound method's return type and the handle class it is a member of
 * (void for a static member or a free function) */
template <typename F>
struct BoundSignature;

template <typename R, typename C, typename... P>
struct BoundSignature<R (C::*)(P...)>
{
	using Return = R;
	using Class = C;
	static constexpr size_t arity = sizeof...(P);
	template <size_t I>
	using Param = std::tuple_element_t<I, std::tuple<P...>>;
};

template <typename R, typename C, typename... P>
struct BoundSignature<R (C::*)(P...) const> : BoundSignature<R (C::*)(P...)>
{
};

template <typename R, typename... P>
struct BoundSignature<R (*)(P...)>
{
	using Return = R;
	using Class = void;
	static constexpr size_t arity = sizeof...(P);
	template <size_t I>
	using Param = std::tuple_element_t<I, std::tuple<P...>>;
};

/*
 * The direct entry (TypeOps.h) of a hot operation, generated from the handle
 * member the method delegates to: the op's arguments become the member's
 * parameters (a zval * parameter takes &argv[i], a bool the truthiness of
 * argv[i], a zend_string * its string, a HashTable * its array), and the
 * member's result becomes the entry's zv::Val — a PT_TRI_* zend_long through
 * pt_op_trinary(), a bool with a trailing `bool &` out parameter through
 * pt_op_bool(). What the hand-written PT_OP_LAMBDA spelled out.
 */
template <auto M>
struct BoundOp
{
	using Signature = BoundSignature<decltype(M)>;
	using Result = typename Signature::Return;
	static constexpr bool boolOut = std::is_same_v<Result, bool>;
	static constexpr size_t params = Signature::arity - (boolOut ? 1 : 0);

	template <size_t I>
	static zend_always_inline typename Signature::template Param<I> argument(zval *argv)
	{
		using P = typename Signature::template Param<I>;
		if constexpr (std::is_same_v<P, zval *>) {
			return &argv[I];
		} else if constexpr (std::is_same_v<P, bool>) {
			return Z_TYPE(argv[I]) == IS_TRUE;
		} else if constexpr (std::is_same_v<P, zend_string *>) {
			return Z_STR(argv[I]);
		} else if constexpr (std::is_same_v<P, HashTable *>) {
			return Z_ARR(argv[I]);
		} else {
			static_assert(std::is_same_v<P, zend_long>, "the direct entry cannot pass this parameter type");
			return Z_LVAL(argv[I]);
		}
	}

	template <typename... A>
	static zend_always_inline decltype(auto) invoke(zend_object *self, A &&...args)
	{
		if constexpr (std::is_void_v<typename Signature::Class>) {
			return M(std::forward<A>(args)...);
		} else {
			return (typename Signature::Class(self).*M)(std::forward<A>(args)...);
		}
	}

	template <size_t... I>
	static zend_always_inline zv::Val run(zend_object *self, zval *argv, std::index_sequence<I...>)
	{
		if constexpr (boolOut) {
			bool out = false;
			bool ok = invoke(self, argument<I>(argv)..., out);
			return pt_op_bool(ok, out);
		} else if constexpr (std::is_same_v<Result, zend_long>) {
			return pt_op_trinary(invoke(self, argument<I>(argv)...));
		} else {
			static_assert(std::is_same_v<Result, zv::Val>, "a direct entry returns zv::Val, a PT_TRI_* zend_long, or bool with a trailing bool & out parameter");
			return invoke(self, argument<I>(argv)...);
		}
	}

	static zv::Val fn(zend_object *self, zend_class_entry *scope, uint32_t argc, zval *argv)
	{
		(void) scope;
		(void) argc;
		return run(self, argv, std::make_index_sequence<params>{});
	}
};

/*
 * The handler Class::method<M, K...>() registers — what a glue lambda that
 * only parses its parameters and delegates spells out by hand: the
 * parameters parsed as the zp kinds K, then M called with them on the $this
 * handle (directly when M is static or free). A zv::Val result is the
 * return value (UNDEF = pending exception); a void one leaves null; a bool
 * one with a trailing `bool &` out parameter is the success flag, the out
 * parameter the returned bool.
 */
template <auto M, typename... K>
struct Bound
{
	using Signature = BoundSignature<decltype(M)>;

	template <typename... A>
	static zend_always_inline decltype(auto) call(zend_execute_data *execute_data, A &&...args)
	{
		if constexpr (std::is_void_v<typename Signature::Class>) {
			return M(std::forward<A>(args)...);
		} else {
			return (typename Signature::Class(Z_OBJ_P(ZEND_THIS)).*M)(std::forward<A>(args)...);
		}
	}

	template <typename... A>
	static zend_always_inline void invoke(zend_execute_data *execute_data, zval *return_value, A &&...args)
	{
		using R = typename Signature::Return;
		if constexpr (std::is_same_v<R, zv::Val>) {
			PT_RETURN_VAL(call(execute_data, std::forward<A>(args)...));
		} else if constexpr (std::is_void_v<R>) {
			call(execute_data, std::forward<A>(args)...);
		} else {
			static_assert(std::is_same_v<R, bool> && Signature::arity == sizeof...(K) + 1, "a bound method returns zv::Val, void, or bool with a trailing bool & out parameter");
			bool out = false;
			if (UNEXPECTED(!call(execute_data, std::forward<A>(args)..., out))) RETURN_THROWS();
			RETURN_BOOL(out);
		}
	}

	/* MSVC names the types of the discarded if-constexpr branches in handle()
	 * too, so an index past the pack resolves to a placeholder kind instead of
	 * failing tuple_element_t */
	struct NoKind { using type = int; };
	template <size_t I>
	using At = std::tuple_element_t<(I < sizeof...(K) ? I : sizeof...(K)), std::tuple<K..., NoKind>>;

	/* the destinations are uninitialized locals, as the declarations above a
	 * hand-written ZEND_PARSE_PARAMETERS block leave them */
	static void ZEND_FASTCALL handle(INTERNAL_FUNCTION_PARAMETERS)
	{
		if constexpr (sizeof...(K) == 0) {
			ZEND_PARSE_PARAMETERS_NONE();
			invoke(execute_data, return_value);
		} else if constexpr (sizeof...(K) == 1) {
			typename At<0>::type a0;
			if (!zp::parse<K...>(execute_data, a0)) RETURN_THROWS();
			invoke(execute_data, return_value, a0);
		} else if constexpr (sizeof...(K) == 2) {
			typename At<0>::type a0;
			typename At<1>::type a1;
			if (!zp::parse<K...>(execute_data, a0, a1)) RETURN_THROWS();
			invoke(execute_data, return_value, a0, a1);
		} else if constexpr (sizeof...(K) == 3) {
			typename At<0>::type a0;
			typename At<1>::type a1;
			typename At<2>::type a2;
			if (!zp::parse<K...>(execute_data, a0, a1, a2)) RETURN_THROWS();
			invoke(execute_data, return_value, a0, a1, a2);
		} else if constexpr (sizeof...(K) == 4) {
			typename At<0>::type a0;
			typename At<1>::type a1;
			typename At<2>::type a2;
			typename At<3>::type a3;
			if (!zp::parse<K...>(execute_data, a0, a1, a2, a3)) RETURN_THROWS();
			invoke(execute_data, return_value, a0, a1, a2, a3);
		} else {
			static_assert(sizeof...(K) <= 4, "bind at most four parameters; write the glue by hand beyond that");
		}
	}
};

} // namespace detail

/*
 * Builder for one class. Usage:
 *
 *   reg::Class cls("PHPStanTurbo\\Foo");
 *   cls.privateLongProperty("value", 0);
 *   cls.method("bar", reg::Public, 1, { reg::longArg("x") },
 *       [](INTERNAL_FUNCTION_PARAMETERS) { ... });
 *   ce = cls.register_();
 */
class Class
{
public:
	explicit Class(const char *name) : name(name) {}

	/* the PHP twin is final — the declared class is too */
	Class &final()
	{
		flags |= ZEND_ACC_FINAL;
		return *this;
	}

	/* the PHP twin is abstract — the declared class is too */
	Class &abstract_()
	{
		flags |= ZEND_ACC_EXPLICIT_ABSTRACT_CLASS;
		return *this;
	}

	/* the PHP twin's parent class (real name; a shadowed parent resolves to
	 * its native class, anything else autoloads at activation) */
	Class &parent(const char *parentName)
	{
		this->parentName = parentName;
		return *this;
	}

	/* the PHP twin's own implements clause (real interface names, resolved
	 * through the autoloader at activation) */
	Class &implements(std::initializer_list<const char *> interfaceNames)
	{
		for (const char *interfaceName : interfaceNames) {
			interfaces.push_back(interfaceName);
		}
		return *this;
	}

	/*
	 * requiredArgs is ZEND_BEGIN_ARG_INFO_EX's required_num_args; args carry
	 * name/type/by-ref/variadic exactly as the ZEND_ARG_* macros would.
	 */
	Class &method(const char *methodName, uint32_t flags, uint32_t requiredArgs, std::initializer_list<Arg> args, zif_handler handler, const Arg *returns = NULL)
	{
		return method(methodName, flags, requiredArgs, args.begin(), args.size(), handler, returns);
	}

	Class &method(const char *methodName, uint32_t flags, uint32_t requiredArgs, const Arg *args, size_t argc, zif_handler handler, const Arg *returns)
	{
		/* arginfo array: slot 0 is the return-info slot carrying the
		 * required-args count, exactly as ZEND_BEGIN_ARG_INFO_EX emits.
		 * A declared return type goes in the same slot's type — needed only
		 * where the engine enforces it (implementing a userland interface). */
		auto *argInfo = (zend_internal_arg_info *) pemalloc(sizeof(zend_internal_arg_info) * (argc + 1), 1);
		argInfo[0].name = (const char *) (uintptr_t) requiredArgs;
		argInfo[0].type.ptr = returns != NULL ? (void *) returns->className : NULL;
		argInfo[0].type.type_mask = returns != NULL ? returns->typeMask : 0;
		argInfo[0].default_value = NULL;
		for (size_t i = 0; i < argc; i++) {
			argInfo[i + 1].name = args[i].name;
			argInfo[i + 1].type.ptr = (void *) args[i].className;
			argInfo[i + 1].type.type_mask = args[i].typeMask;
			argInfo[i + 1].default_value = args[i].defaultValue;
		}

		zend_function_entry entry;
		memset(&entry, 0, sizeof(entry));
		entry.fname = methodName;
		entry.handler = handler;
		entry.arg_info = argInfo;
		entry.num_args = (uint32_t) argc;
		entry.flags = flags;
		entries.push_back(entry);
		return *this;
	}

	/*
	 * A method with a generated handler: the parameters parsed as the zp
	 * kinds K and delegated to M (see detail::Bound), the required-args
	 * count derived from the kinds.
	 */
	template <auto M, typename... K>
	Class &method(const char *methodName, uint32_t flags, std::initializer_list<Arg> args, const Arg *returns = NULL)
	{
		return method(methodName, flags, zp::required<K...>(), args, &detail::Bound<M, K...>::handle, returns);
	}

	/* the method of a generated signature (turbo-ext/src/generated) */
	Class &method(const Sig &sig, zif_handler handler)
	{
		return method(sig.name, sig.flags, sig.requiredArgs, sig.args, sig.argc, handler, sig.returns);
	}

	/* the method of a generated signature with a generated handler; the
	 * parameter kinds must match the signature — a mismatch (the twin's
	 * signature changed, the binding did not) refuses to load the module */
	template <auto M, typename... K>
	Class &method(const Sig &sig)
	{
		if (UNEXPECTED(sig.argc != sizeof...(K) || sig.requiredArgs != zp::required<K...>())) {
			zend_error_noreturn(E_CORE_ERROR, "phpstan_turbo: %s::%s() binds %u parameter kinds to a signature of %u (%u required)", name, sig.name, (unsigned) sizeof...(K), (unsigned) sig.argc, (unsigned) sig.requiredArgs);
		}
		return method(sig.name, sig.flags, sig.requiredArgs, sig.args, sig.argc, &detail::Bound<M, K...>::handle, sig.returns);
	}

	/* whether a method of that name is already declared on the builder
	 * (case-insensitive, like the engine's function table) */
	bool hasMethod(const char *methodName) const
	{
		for (const zend_function_entry &entry : entries) {
			if (strcasecmp(entry.fname, methodName) == 0) return true;
		}
		return false;
	}

	/*
	 * A method contributed by a PHP trait (the pt_type_trait_* registrars in
	 * TypeTraits.cpp): registered only when the class did not declare a
	 * method of that name itself — the class body wins over a used trait,
	 * exactly the precedence PHP applies. The class's own methods must
	 * therefore be registered before its trait registrars run.
	 */
	Class &traitMethod(const char *methodName, uint32_t flags, uint32_t requiredArgs, std::initializer_list<Arg> args, zif_handler handler, const Arg *returns = NULL)
	{
		lastTraitMethodAdded = !hasMethod(methodName);
		if (!lastTraitMethodAdded) return *this;
		return method(methodName, flags, requiredArgs, args, handler, returns);
	}

	Class &traitMethod(const Sig &sig, zif_handler handler)
	{
		lastTraitMethodAdded = !hasMethod(sig.name);
		if (!lastTraitMethodAdded) return *this;
		return method(sig, handler);
	}

	/*
	 * The direct entry of a hot operation (TypeOps.h) — declared right next
	 * to the cls.method(...) line of the method it mirrors, delegating to
	 * the same handle-class member. The class must declare the method
	 * itself (an inherited method's entry is inherited with it at
	 * activation; registering an entry for a method the class does not
	 * declare would shadow the parent's body).
	 */
	/* the direct entry generated from the handle member the op's method
	 * delegates to (detail::BoundOp) */
	template <pt_type_op_id Op, auto M>
	Class &op()
	{
		checkOpArity(Op, detail::BoundOp<M>::params);
		return op(Op, &detail::BoundOp<M>::fn);
	}

	template <pt_type_op_id Op, auto M>
	Class &traitOp()
	{
		checkOpArity(Op, detail::BoundOp<M>::params);
		return traitOp(Op, &detail::BoundOp<M>::fn);
	}

	/* a generated entry whose member takes fewer or more arguments than the
	 * op passes would silently read the wrong argv slots */
	/* the member may take fewer parameters than the op passes — it ignores
	 * the trailing arguments, as the hand-written lambda did — but never
	 * more: those would read past the argv the op gives it */
	void checkOpArity(pt_type_op_id op, size_t params) const
	{
		if (UNEXPECTED(params > pt_type_op_infos[op].argc)) {
			zend_error_noreturn(E_CORE_ERROR, "phpstan_turbo: %s's direct entry for %s() takes %u arguments, the op passes only %u", name, pt_type_op_infos[op].lcname, (unsigned) params, (unsigned) pt_type_op_infos[op].argc);
		}
	}

	Class &op(pt_type_op_id op, pt_type_op_fn fn)
	{
		if (UNEXPECTED(!hasMethod(pt_type_op_infos[op].lcname))) {
			zend_error_noreturn(E_CORE_ERROR, "phpstan_turbo: %s registers a direct entry for %s() without declaring the method", name, pt_type_op_infos[op].lcname);
		}
		opFns[op] = fn;
		return *this;
	}

	/* the direct entry of the trait method registered by the immediately
	 * preceding traitMethod() call — recorded only when that call added
	 * the method (the class body, or an earlier trait, wins otherwise,
	 * together with its own entry) */
	Class &traitOp(pt_type_op_id op, pt_type_op_fn fn)
	{
		if (lastTraitMethodAdded) {
			opFns[op] = fn;
		}
		return *this;
	}

	/* declaration order defines the OBJ_PROP_NUM slot, as with the macros */
	Class &privateLongProperty(const char *propertyName, zend_long defaultValue)
	{
		properties.push_back({ propertyName, PropertyKind::Long, ZEND_ACC_PRIVATE, defaultValue });
		return *this;
	}

	/* a private null-initialised property (zend_declare_property_null) */
	Class &privateNullProperty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::Null, ZEND_ACC_PRIVATE, 0 });
		return *this;
	}

	/* a protected property defaulting to an empty array (zend_declare_property
	 * with ZVAL_EMPTY_ARRAY) */
	Class &protectedArrayProperty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::EmptyArray, ZEND_ACC_PROTECTED, 0 });
		return *this;
	}

	Class &privateArrayProperty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::EmptyArray, ZEND_ACC_PRIVATE, 0 });
		return *this;
	}

	Class &publicArrayProperty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::EmptyArray, ZEND_ACC_PUBLIC, 0 });
		return *this;
	}

	/* a protected bool property (zend_declare_property_bool) */
	Class &protectedBoolProperty(const char *propertyName, bool defaultValue)
	{
		properties.push_back({ propertyName, PropertyKind::Bool, ZEND_ACC_PROTECTED, defaultValue ? 1 : 0 });
		return *this;
	}

	/* a `public readonly` typed property with no default (IS_PROP_UNINIT),
	 * as a promoted readonly constructor parameter declares; typeMask is a
	 * MAY_BE_* mask */
	Class &publicReadonlyProperty(const char *propertyName, uint32_t typeMask)
	{
		properties.push_back({ propertyName, PropertyKind::Typed, ZEND_ACC_PUBLIC | ZEND_ACC_READONLY, (zend_long) typeMask });
		return *this;
	}

	/* a `private` typed property with no default (IS_PROP_UNINIT until the
	 * constructor writes it), as a promoted `private bool $value` declares;
	 * typeMask is a MAY_BE_* mask */
	Class &privateTypedProperty(const char *propertyName, uint32_t typeMask)
	{
		properties.push_back({ propertyName, PropertyKind::Typed, ZEND_ACC_PRIVATE, (zend_long) typeMask });
		return *this;
	}

	/* a `private Foo $x` / `private ?Foo $x` class-typed property with no
	 * default (IS_PROP_UNINIT until the constructor writes it); className
	 * is a persistent literal */
	Class &privateTypedClassProperty(const char *propertyName, const char *className, bool nullable)
	{
		properties.push_back({ propertyName, PropertyKind::Typed, ZEND_ACC_PRIVATE, nullable ? (zend_long) MAY_BE_NULL : 0, className });
		return *this;
	}

	/* a `private ?Foo $x = null` class-typed property; extraMask adds the
	 * scalar members of a `private Foo|false|null $x = null` (MAY_BE_FALSE) */
	Class &privateTypedClassPropertyDefaultNull(const char *propertyName, const char *className, uint32_t extraMask = 0)
	{
		properties.push_back({ propertyName, PropertyKind::TypedNull, ZEND_ACC_PRIVATE, (zend_long) (MAY_BE_NULL | extraMask), className });
		return *this;
	}

	/* a `private Foo|Bar $x` union-of-classes typed property with no default
	 * (IS_PROP_UNINIT until the constructor writes it); classNames is a
	 * persistent `|`-separated literal */
	Class &privateTypedClassUnionProperty(const char *propertyName, const char *classNames)
	{
		properties.push_back({ propertyName, PropertyKind::TypedClassUnion, ZEND_ACC_PRIVATE, 0, classNames });
		return *this;
	}

	/* a `private array $x = []` typed property */
	Class &privateTypedArrayPropertyDefaultEmpty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::TypedEmptyArray, ZEND_ACC_PRIVATE, (zend_long) MAY_BE_ARRAY });
		return *this;
	}

	/* a `private ?string $x = null` scalar-typed property defaulting to null;
	 * typeMask is the MAY_BE_* mask without the null bit */
	Class &privateTypedPropertyDefaultNull(const char *propertyName, uint32_t typeMask)
	{
		properties.push_back({ propertyName, PropertyKind::TypedNull, ZEND_ACC_PRIVATE, (zend_long) (typeMask | MAY_BE_NULL) });
		return *this;
	}

	/* the twin's `private static array $x = []` / `private static ?Foo $x =
	 * null` / `private static ?string $x = null` typed static properties */
	Class &privateStaticTypedArrayPropertyDefaultEmpty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::TypedEmptyArray, ZEND_ACC_PRIVATE | ZEND_ACC_STATIC, (zend_long) MAY_BE_ARRAY });
		return *this;
	}

	Class &privateStaticTypedClassPropertyDefaultNull(const char *propertyName, const char *className)
	{
		properties.push_back({ propertyName, PropertyKind::TypedNull, ZEND_ACC_PRIVATE | ZEND_ACC_STATIC, (zend_long) MAY_BE_NULL, className });
		return *this;
	}

	Class &privateStaticTypedPropertyDefaultNull(const char *propertyName, uint32_t typeMask)
	{
		properties.push_back({ propertyName, PropertyKind::TypedNull, ZEND_ACC_PRIVATE | ZEND_ACC_STATIC, (zend_long) (typeMask | MAY_BE_NULL) });
		return *this;
	}

	/* a `private readonly` typed property with no default (IS_PROP_UNINIT),
	 * as a promoted `private readonly string $value` declares; typeMask is
	 * a MAY_BE_* mask */
	Class &privateReadonlyTypedProperty(const char *propertyName, uint32_t typeMask)
	{
		properties.push_back({ propertyName, PropertyKind::Typed, ZEND_ACC_PRIVATE | ZEND_ACC_READONLY, (zend_long) typeMask });
		return *this;
	}

	/* the twin's `private static array $x;` / `private static Foo $x;`
	 * typed static properties with no default (uninitialized until the
	 * first write); the class name "self" stands for the declared class */
	Class &privateStaticTypedArrayProperty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::Typed, ZEND_ACC_PRIVATE | ZEND_ACC_STATIC, (zend_long) MAY_BE_ARRAY });
		return *this;
	}

	Class &privateStaticTypedClassProperty(const char *propertyName, const char *className)
	{
		properties.push_back({ propertyName, PropertyKind::Typed, ZEND_ACC_PRIVATE | ZEND_ACC_STATIC, 0, className });
		return *this;
	}

	/* a `private bool $x = false` / `= true` typed property */
	Class &privateTypedBoolProperty(const char *propertyName, bool defaultValue)
	{
		properties.push_back({ propertyName, PropertyKind::TypedBool, ZEND_ACC_PRIVATE, defaultValue ? 1 : 0 });
		return *this;
	}

	/* a `public readonly Foo $x` class-typed property with no default
	 * (IS_PROP_UNINIT until the constructor writes it); className is a
	 * persistent literal */
	Class &publicReadonlyTypedClassProperty(const char *propertyName, const char *className)
	{
		properties.push_back({ propertyName, PropertyKind::Typed, ZEND_ACC_PUBLIC | ZEND_ACC_READONLY, 0, className });
		return *this;
	}

	/* a public long class constant (zend_declare_class_constant_long) */
	Class &classConstantLong(const char *constantName, zend_long value)
	{
		constants.push_back({ constantName, value, ZEND_ACC_PUBLIC });
		return *this;
	}

	/* a `private const X = <int>` class constant */
	Class &privateClassConstantLong(const char *constantName, zend_long value)
	{
		constants.push_back({ constantName, value, ZEND_ACC_PRIVATE });
		return *this;
	}

	/* a `private const X = '<string>'` class constant; value is a persistent
	 * literal */
	Class &privateClassConstantString(const char *constantName, const char *value)
	{
		constants.push_back({ constantName, 0, ZEND_ACC_PRIVATE, value });
		return *this;
	}

	/* a `public const X = '<string>'` class constant; value is a persistent
	 * literal */
	Class &publicClassConstantString(const char *constantName, const char *value)
	{
		constants.push_back({ constantName, 0, ZEND_ACC_PUBLIC, value });
		return *this;
	}

	/* a `public const X = [...]` class constant whose value the builder
	 * fills in at declaration (a persistent, immutable value — the engine
	 * references it for the process lifetime) */
	/* a `private int $x = 0` typed property with an int default */
	Class &privateTypedLongProperty(const char *propertyName, zend_long defaultValue)
	{
		properties.push_back({ propertyName, PropertyKind::TypedLong, ZEND_ACC_PRIVATE, defaultValue });
		return *this;
	}

	Class &classConstantValue(const char *constantName, void (*buildValue)(zval *out))
	{
		constants.push_back({ constantName, 0, ZEND_ACC_PUBLIC, nullptr, buildValue });
		return *this;
	}

	/* a `private const X = [...]` class constant, filled in like
	 * classConstantValue() */
	Class &privateClassConstantValue(const char *constantName, void (*buildValue)(zval *out))
	{
		constants.push_back({ constantName, 0, ZEND_ACC_PRIVATE, nullptr, buildValue });
		return *this;
	}

	/* an internal class registered at module startup (extension-only
	 * classes with no PHP twin) */
	zend_class_entry *register_()
	{
		zend_function_entry sentinel;
		memset(&sentinel, 0, sizeof(sentinel));
		entries.push_back(sentinel);

		/* the engine copies fentry contents but references arg_info forever;
		 * the entries vector lives only through this call, argInfo persists */
		zend_class_entry ce;
		INIT_CLASS_ENTRY_EX(ce, name, strlen(name), entries.data());
		zend_class_entry *registered = zend_register_internal_class(&ce);
		registered->ce_flags |= flags;
		declareMembers(registered, properties, constants);
		return registered;
	}

	/* a class shadowing a PHP implementation: recorded now, declared under
	 * the twin's name by Runtime::activateShadowing(); *out is set then */
	void shadow(zend_class_entry **out)
	{
		zend_function_entry sentinel;
		memset(&sentinel, 0, sizeof(sentinel));
		entries.push_back(sentinel);

		reg::ShadowPlan plan;
		plan.name = name;
		plan.flags = flags;
		plan.parentName = parentName;
		plan.interfaces = std::move(interfaces);
		plan.entries = std::move(entries);
		plan.properties = std::move(properties);
		plan.constants = std::move(constants);
		plan.out = out;
		plan.ce = NULL;
		memcpy(plan.opFns, opFns, sizeof(opFns));
		plan.ops = NULL;
		plan.differentialOnly = differentialOnly;
		pt_shadow_plan_add(std::move(plan));
	}

	/* a typed property of any visibility and shape — the builders above
	 * are the common cases; this spells the twin's declaration directly:
	 * visibility is the ZEND_ACC_* flags (ZEND_ACC_PRIVATE | ZEND_ACC_STATIC,
	 * ZEND_ACC_PUBLIC | ZEND_ACC_READONLY, ...), kind the shape, mask the
	 * MAY_BE_* type mask (with MAY_BE_NULL for a nullable class type; the
	 * bool/int default for TypedBool/TypedLong), className the persistent
	 * literal of a class-typed property ("self" for the declared class) or
	 * NULL. Promoted constructor properties are declared this way: they
	 * never carry the parameter's default, so their kind is Typed. */
	Class &property(const char *propertyName, uint32_t visibility, PropertyKind kind, zend_long mask, const char *className = nullptr)
	{
		properties.push_back({ propertyName, kind, visibility, mask, className });
		return *this;
	}

	/* a plan for a class whose port is incomplete: declared next to the
	 * twin by the prefixed activation of the differential tests only, so
	 * the partial native class can be compared method by method, and never
	 * under the real name (the twin keeps running everywhere else). The
	 * finished port replaces this call by shadow(). */
	void shadowDifferentialOnly(zend_class_entry **out)
	{
		differentialOnly = true;
		shadow(out);
	}

private:
	const char *name;
	uint32_t flags = 0;
	const char *parentName = nullptr;
	std::vector<const char *> interfaces;
	std::vector<zend_function_entry> entries;
	std::vector<Property> properties;
	std::vector<Constant> constants;
	pt_type_op_fn opFns[PT_OP_COUNT] = {};
	bool lastTraitMethodAdded = false;
	bool differentialOnly = false;
};

} // namespace reg

#endif
