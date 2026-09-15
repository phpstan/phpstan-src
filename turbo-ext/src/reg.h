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
#include "zv.h"

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
constexpr uint32_t Private = ZEND_ACC_PRIVATE;
constexpr uint32_t Static = ZEND_ACC_STATIC;
constexpr uint32_t PublicStatic = ZEND_ACC_PUBLIC | ZEND_ACC_STATIC;

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
inline Arg withDefault(Arg arg, const char *defaultValue)
{
	arg.defaultValue = defaultValue;
	return arg;
}

namespace detail {

inline uint32_t flagBits(bool byRef, bool variadic)
{
	return _ZEND_ARG_INFO_FLAGS(byRef ? ZEND_SEND_BY_REF : ZEND_SEND_BY_VAL, variadic ? 1 : 0, 0);
}

inline uint32_t codeMask(zend_uchar code, bool nullable)
{
	uint32_t mask = code == _IS_BOOL ? MAY_BE_BOOL : (uint32_t) (1u << code);
	return mask | (nullable ? MAY_BE_NULL : 0);
}

} // namespace detail

/* an untyped parameter (ZEND_ARG_INFO) */
inline Arg any(const char *name, bool byRef = false)
{
	return { name, detail::flagBits(byRef, false), nullptr };
}

inline Arg longArg(const char *name)
{
	return { name, detail::codeMask(IS_LONG, false) | detail::flagBits(false, false), nullptr };
}

inline Arg boolArg(const char *name)
{
	return { name, detail::codeMask(_IS_BOOL, false) | detail::flagBits(false, false), nullptr };
}

inline Arg stringArg(const char *name, bool nullable = false)
{
	return { name, detail::codeMask(IS_STRING, nullable) | detail::flagBits(false, false), nullptr };
}

inline Arg arrayArg(const char *name)
{
	return { name, detail::codeMask(IS_ARRAY, false) | detail::flagBits(false, false), nullptr };
}

inline Arg callableArg(const char *name)
{
	return { name, MAY_BE_CALLABLE | detail::flagBits(false, false), nullptr };
}

inline Arg objectArg(const char *name, bool nullable = false)
{
	return { name, detail::codeMask(IS_OBJECT, nullable) | detail::flagBits(false, false), nullptr };
}

/* object of a specific class; className must be a persistent literal */
inline Arg obj(const char *name, const char *className, bool nullable = false)
{
	return { name, _ZEND_TYPE_LITERAL_NAME_BIT | (nullable ? MAY_BE_NULL : 0) | detail::flagBits(false, false), className };
}

inline Arg variadicObj(const char *name, const char *className)
{
	return { name, _ZEND_TYPE_LITERAL_NAME_BIT | detail::flagBits(false, true), className };
}

enum class PropertyKind
{
	Long,
	Null,
	Bool,
	EmptyArray,
	PublicReadonlyTyped, /* UNDEF default, defaultValue carries the MAY_BE_* type mask */
};

struct Property
{
	const char *name;
	PropertyKind kind;
	uint32_t visibility;
	zend_long defaultValue;
};

struct Constant
{
	const char *name;
	zend_long value;
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
			case PropertyKind::PublicReadonlyTyped: {
				zend_string *nameStr = zend_string_init(property.name, len, ce->type == ZEND_INTERNAL_CLASS);
				zval undef;
				ZVAL_UNDEF(&undef);
				zend_type type = ZEND_TYPE_INIT_MASK((uint32_t) property.defaultValue);
				zend_declare_typed_property(ce, nameStr, &undef, ZEND_ACC_PUBLIC | ZEND_ACC_READONLY, NULL, type);
				zend_string_release(nameStr);
				break;
			}
		}
	}
	for (const Constant &constant : constants) {
		zend_declare_class_constant_long(ce, constant.name, strlen(constant.name), constant.value);
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
			bool out;
			if (UNEXPECTED(!call(execute_data, std::forward<A>(args)..., out))) RETURN_THROWS();
			RETURN_BOOL(out);
		}
	}

	template <size_t I>
	using At = std::tuple_element_t<I, std::tuple<K...>>;

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
		/* arginfo array: slot 0 is the return-info slot carrying the
		 * required-args count, exactly as ZEND_BEGIN_ARG_INFO_EX emits.
		 * A declared return type goes in the same slot's type — needed only
		 * where the engine enforces it (implementing a userland interface). */
		auto *argInfo = (zend_internal_arg_info *) pemalloc(sizeof(zend_internal_arg_info) * (args.size() + 1), 1);
		argInfo[0].name = (const char *) (uintptr_t) requiredArgs;
		argInfo[0].type.ptr = returns != NULL ? (void *) returns->className : NULL;
		argInfo[0].type.type_mask = returns != NULL ? returns->typeMask : 0;
		argInfo[0].default_value = NULL;
		size_t i = 1;
		for (const Arg &arg : args) {
			argInfo[i].name = arg.name;
			argInfo[i].type.ptr = (void *) arg.className;
			argInfo[i].type.type_mask = arg.typeMask;
			argInfo[i].default_value = arg.defaultValue;
			i++;
		}

		zend_function_entry entry;
		memset(&entry, 0, sizeof(entry));
		entry.fname = methodName;
		entry.handler = handler;
		entry.arg_info = argInfo;
		entry.num_args = (uint32_t) args.size();
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
		properties.push_back({ propertyName, PropertyKind::PublicReadonlyTyped, ZEND_ACC_PUBLIC | ZEND_ACC_READONLY, (zend_long) typeMask });
		return *this;
	}

	/* a public long class constant (zend_declare_class_constant_long) */
	Class &classConstantLong(const char *constantName, zend_long value)
	{
		constants.push_back({ constantName, value });
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
		pt_shadow_plan_add(std::move(plan));
	}

private:
	const char *name;
	uint32_t flags = 0;
	const char *parentName = nullptr;
	std::vector<const char *> interfaces;
	std::vector<zend_function_entry> entries;
	std::vector<Property> properties;
	std::vector<Constant> constants;
};

} // namespace reg

#endif
