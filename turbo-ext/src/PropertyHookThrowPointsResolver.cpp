/*
 * PHPStanTurbo\PropertyHookThrowPointsResolver — native implementation of
 * PHPStan\Analyser\PropertyHookThrowPointsResolver.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. getThrowPointsFromPropertyHook() runs for
 * every named property fetch (and property write) while property hooks are
 * supported, so it is exported as
 * pt_property_hook_throw_points_resolver_get_throw_points_from_property_hook().
 *
 * The property reflection's getters — PhpPropertyReflection::getDeclaringClass()
 * / hasHook() / isPrivate() / isFinal() / getHook() — are the native bodies
 * reached through the property-reflection dispatch
 * (pt_extended_property_reflection_call()), the hook's getThrowType() through
 * the method-reflection one; PhpMethodFromParserNodeReflection::isPropertyHook()
 * / getHookedPropertyName() only return a declared slot of their final class,
 * so the slot is read in place (a pt_property_site).
 *
 * The file also carries the property-reflection readers the fetch handlers
 * share (pt_property_reflection_*), now thin names for that dispatch.
 */

#include "support.h"
#include "generated/PropertyHookThrowPointsResolver.h"

namespace slots = ptdecl::PropertyHookThrowPointsResolver::slot;
namespace sigs = ptdecl::PropertyHookThrowPointsResolver::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"

zend_class_entry *pt_ce_property_hook_throw_points_resolver = nullptr;

namespace {

/* {{{ declared slots of the PHP reflection classes */

pt_property_site pt_phtpr_method_hook_for_property_site;
pt_property_site pt_phtpr_fetch_var_site;
pt_property_site pt_phtpr_fetch_name_site;
pt_property_site pt_phtpr_variable_name_site;
pt_property_site pt_phtpr_identifier_name_site;
pt_method_site pt_phtpr_is_property_hook_site;
pt_method_site pt_phtpr_get_hooked_property_name_site;

zval pt_phtpr_undef;

/* $object->$name of a declared property (dereferenced, borrowed); an UNDEF
 * zval when the class declares no such property */
zval *declaredSlot(pt_property_site &site, zval *object, const char *name, size_t len)
{
	zval *slot = pt_property_cached(site, Z_OBJ_P(object), name, len);
	if (UNEXPECTED(slot == NULL)) {
		ZVAL_UNDEF(&pt_phtpr_undef);
		return &pt_phtpr_undef;
	}
	ZVAL_DEREF(slot);
	return slot;
}

/* whether the object is exactly the class-map class (never autoloads a
 * class the object cannot be an instance of) */
inline bool isExactly(zval *object, int classIdx)
{
	zend_class_entry *ce = pt_class_loaded(classIdx);
	return ce != NULL && Z_OBJCE_P(object) == ce;
}

/* a bool getter's result; false = pending exception */
[[nodiscard]] bool callBool(pt_method_site &site, zval *object, const char *lcname, size_t len, uint32_t argc, zval *argv, bool &out)
{
	zv::Val result = pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */

/* {{{ PhpPropertyReflection getters (the native bodies through the
 * property-reflection dispatch of ResolvedPropertyReflection.cpp) */

zv::Val phpPropertyGetDeclaringClass(zval *reflection)
{
	return pt_extended_property_reflection_call(reflection, PT_PROP_GET_DECLARING_CLASS);
}

/* $reflection->hasHook($hookType) */
[[nodiscard]] bool phpPropertyHasHook(zval *reflection, zend_string *hookType, bool &out)
{
	return pt_extended_property_reflection_has_hook(reflection, hookType, out);
}

[[nodiscard]] bool phpPropertyIsPrivate(zval *reflection, bool &out)
{
	return pt_extended_property_reflection_bool(reflection, PT_PROP_IS_PRIVATE, out);
}

/* $reflection->isFinal()->yes() */
[[nodiscard]] bool phpPropertyIsFinalYes(zval *reflection, bool &out)
{
	zv::Val isFinal = pt_extended_property_reflection_call(reflection, PT_PROP_IS_FINAL);
	if (UNEXPECTED(isFinal.isUndef())) return false;
	if (UNEXPECTED(!isFinal.ref().isObject())) {
		zend_throw_error(NULL, "Call to a member function yes() on %s", zend_zval_value_name(isFinal.raw()));
		return false;
	}
	zend_long value = pt_type_trinary_value(isFinal.raw());
	if (UNEXPECTED(value < 0)) return false;
	out = value == PT_TRI_YES;
	return true;
}

/* $reflection->getHook($hookType) */
zv::Val phpPropertyGetHook(zval *reflection, zend_string *hookType)
{
	return pt_extended_property_reflection_get_hook(reflection, hookType);
}

/* }}} */

/* {{{ PhpMethodFromParserNodeReflection getters */

[[nodiscard]] bool methodIsPropertyHook(zval *method, bool &out)
{
	if (EXPECTED(isExactly(method, PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION))) {
		zval *slot = declaredSlot(pt_phtpr_method_hook_for_property_site, method, PT_LC("hookForProperty"));
		if (EXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) {
			out = Z_TYPE_P(slot) != IS_NULL;
			return true;
		}
	}
	return callBool(pt_phtpr_is_property_hook_site, method, PT_LC("ispropertyhook"), 0, NULL, out);
}

zv::Val methodGetHookedPropertyName(zval *method)
{
	if (EXPECTED(isExactly(method, PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION))) {
		zval *slot = declaredSlot(pt_phtpr_method_hook_for_property_site, method, PT_LC("hookForProperty"));
		if (EXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) return zv::Val::copyOf(zv::Ref(slot));
	}
	return pt_call_method_cached(pt_phtpr_get_hooked_property_name_site, Z_OBJ_P(method), PT_LC("gethookedpropertyname"), 0, NULL);
}

/* }}} */

/* $propertyFetch->$name of a php-parser node (dereferenced, borrowed); NULL
 * with the undefined-property Error pending */
zval *nodeProperty(pt_property_site &site, zval *node, const char *name, size_t len)
{
	zval *value = pt_property_cached(site, Z_OBJ_P(node), name, len);
	if (EXPECTED(value != NULL)) {
		ZVAL_DEREF(value);
		if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) return value;
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
		return NULL;
	}
	zend_throw_error(NULL, "Undefined property: %s::$%s", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
	return NULL;
}

/* $value instanceof <class-map class>; -1 = pending exception */
inline int isInstanceOf(zval *value, int classIdx)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return -1;
	return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce) ? 1 : 0;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\PropertyHookThrowPointsResolver; UNDEF = pending
 * exception. */
class PropertyHookThrowPointsResolver
{
public:
	explicit PropertyHookThrowPointsResolver(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(bool implicitThrows) const
	{
		zval value = {};
		ZVAL_BOOL(&value, implicitThrows);
		pt_write_slot(self, slots::implicitThrows, &value);
	}

	/* Mirrors getThrowPointsFromPropertyHook(). */
	zv::Val getThrowPointsFromPropertyHook(zval *scope, zval *propertyFetch, zval *propertyReflection, zend_string *hookName) const
	{
		zv::Val scopeFunction = pt_mutating_scope_get_function(Z_OBJ_P(scope));
		if (UNEXPECTED(scopeFunction.isUndef())) return zv::Val();
		int isMethodFromParserNode = isInstanceOf(scopeFunction.raw(), PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION);
		if (UNEXPECTED(isMethodFromParserNode < 0)) return zv::Val();
		if (isMethodFromParserNode) {
			bool isPropertyHook;
			if (UNEXPECTED(!methodIsPropertyHook(scopeFunction.raw(), isPropertyHook))) return zv::Val();
			if (isPropertyHook) {
				bool ownHook;
				if (UNEXPECTED(!isOwnHookedPropertyFetch(propertyFetch, scopeFunction.raw(), ownHook))) return zv::Val();
				if (ownHook) return zv::Val(zv::Arr::empty());
			}
		}

		zv::Val declaringClass = phpPropertyGetDeclaringClass(propertyReflection);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		bool hasHook;
		if (UNEXPECTED(!phpPropertyHasHook(propertyReflection, hookName, hasHook))) return zv::Val();
		bool implicitThrows = Z_TYPE_P(OBJ_PROP_NUM(self, slots::implicitThrows)) == IS_TRUE;
		if (!hasHook) {
			bool isPrivate;
			if (UNEXPECTED(!phpPropertyIsPrivate(propertyReflection, isPrivate))) return zv::Val();
			if (isPrivate) return zv::Val(zv::Arr::empty());
			bool isFinal;
			if (UNEXPECTED(!phpPropertyIsFinalYes(propertyReflection, isFinal))) return zv::Val();
			if (isFinal) return zv::Val(zv::Arr::empty());
			if (UNEXPECTED(!declaringClass.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isFinal() on %s", zend_zval_value_name(declaringClass.raw()));
				return zv::Val();
			}
			bool classIsFinal;
			if (UNEXPECTED(!pt_class_reflection_is_final(Z_OBJ_P(declaringClass.raw()), classIsFinal))) return zv::Val();
			if (classIsFinal) return zv::Val(zv::Arr::empty());

			if (implicitThrows) return implicitThrowPoint(scope, propertyFetch);

			return zv::Val(zv::Arr::empty());
		}

		zv::Val getHook = phpPropertyGetHook(propertyReflection, hookName);
		if (UNEXPECTED(getHook.isUndef())) return zv::Val();
		if (UNEXPECTED(!getHook.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getThrowType() on %s", zend_zval_value_name(getHook.raw()));
			return zv::Val();
		}
		zv::Val throwType = pt_extended_method_reflection_call(getHook.raw(), PT_MR_GET_THROW_TYPE);
		if (UNEXPECTED(throwType.isUndef())) return zv::Val();

		if (!throwType.isNull()) {
			if (UNEXPECTED(!throwType.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isVoid() on %s", zend_zval_value_name(throwType.raw()));
				return zv::Val();
			}
			zv::Val isVoid = pt_type_op(Z_OBJ_P(throwType.raw()), PT_OP_IS_VOID, 0, NULL);
			if (UNEXPECTED(isVoid.isUndef())) return zv::Val();
			zend_long isVoidValue = pt_type_trinary_value(isVoid.raw());
			if (UNEXPECTED(isVoidValue < 0)) return zv::Val();
			if (isVoidValue != PT_TRI_YES) {
				zv::Val throwPoint = pt_internal_throw_point_create_explicit(scope, throwType.raw(), propertyFetch, true, false);
				if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
				zv::Arr points = zv::Arr::create(1);
				points.push(std::move(throwPoint));
				return zv::Val(std::move(points));
			}
		} else if (implicitThrows) {
			return implicitThrowPoint(scope, propertyFetch);
		}

		return zv::Val(zv::Arr::empty());
	}

private:
	zend_object *self;

	/* [InternalThrowPoint::createImplicit($scope, $propertyFetch)] */
	static zv::Val implicitThrowPoint(zval *scope, zval *propertyFetch)
	{
		zv::Val throwPoint = pt_internal_throw_point_create_implicit(scope, propertyFetch);
		if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
		zv::Arr points = zv::Arr::create(1);
		points.push(std::move(throwPoint));
		return zv::Val(std::move(points));
	}

	/* $propertyFetch->var instanceof Variable && $propertyFetch->var->name
	 * === 'this' && $propertyFetch->name instanceof Identifier &&
	 * $propertyFetch->name->toString() === $scopeFunction->getHookedPropertyName();
	 * false = pending exception */
	[[nodiscard]] static bool isOwnHookedPropertyFetch(zval *propertyFetch, zval *scopeFunction, bool &out)
	{
		out = false;
		zval *var = nodeProperty(pt_phtpr_fetch_var_site, propertyFetch, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		int isVariable = isInstanceOf(var, PT_CLASS_VARIABLE);
		if (UNEXPECTED(isVariable < 0)) return false;
		if (!isVariable) return true;
		zval *varName = nodeProperty(pt_phtpr_variable_name_site, var, PT_LC("name"));
		if (UNEXPECTED(varName == NULL)) return false;
		if (Z_TYPE_P(varName) != IS_STRING || !zend_string_equals_literal(Z_STR_P(varName), "this")) return true;
		zval *name = nodeProperty(pt_phtpr_fetch_name_site, propertyFetch, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		int isIdentifier = isInstanceOf(name, PT_CLASS_IDENTIFIER);
		if (UNEXPECTED(isIdentifier < 0)) return false;
		if (!isIdentifier) return true;
		zval *identifierName = nodeProperty(pt_phtpr_identifier_name_site, name, PT_LC("name"));
		if (UNEXPECTED(identifierName == NULL)) return false;
		zv::Val hookedPropertyName = methodGetHookedPropertyName(scopeFunction);
		if (UNEXPECTED(hookedPropertyName.isUndef())) return false;
		out = zend_is_identical(identifierName, hookedPropertyName.raw());
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::PropertyHookThrowPointsResolver;

zv::Val pt_property_hook_throw_points_resolver_get_throw_points_from_property_hook(zval *resolver, zval *scope, zval *propertyFetch, zval *propertyReflection, zend_string *hookName)
{
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_property_hook_throw_points_resolver && Z_TYPE_P(propertyReflection) == IS_OBJECT && instanceof_function(Z_OBJCE_P(propertyReflection), pt_ce_php_property_reflection))) {
		return PropertyHookThrowPointsResolver(Z_OBJ_P(resolver)).getThrowPointsFromPropertyHook(scope, propertyFetch, propertyReflection, hookName);
	}
	zv::Args argv{scope, propertyFetch, propertyReflection, hookName};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("getthrowpointsfrompropertyhook"), 4, argv);
}

/* {{{ the property-reflection readers (the property-reflection dispatch of
 * ResolvedPropertyReflection.cpp) */

zv::Val pt_property_reflection_get_declaring_class(zval *reflection)
{
	return pt_extended_property_reflection_call(reflection, PT_PROP_GET_DECLARING_CLASS);
}

bool pt_property_reflection_has_native_type(zval *reflection, bool &out)
{
	return pt_extended_property_reflection_bool(reflection, PT_PROP_HAS_NATIVE_TYPE, out);
}

zv::Val pt_property_reflection_get_native_type(zval *reflection)
{
	return pt_extended_property_reflection_call(reflection, PT_PROP_GET_NATIVE_TYPE);
}

zv::Val pt_property_reflection_get_readable_type(zval *reflection)
{
	return pt_extended_property_reflection_call(reflection, PT_PROP_GET_READABLE_TYPE);
}

zv::Val pt_property_reflection_get_writable_type(zval *reflection)
{
	return pt_extended_property_reflection_call(reflection, PT_PROP_GET_WRITABLE_TYPE);
}

/* }}} */

/* {{{ $phpVersion->supportsPropertyHooks(): `$this->versionId >= 80400` of
 * exactly the final PhpVersion, the method otherwise */

namespace {

pt_property_site pt_pv_version_id_site;
pt_method_site pt_pv_supports_property_hooks_site;

} // namespace

bool pt_php_version_supports_property_hooks(zval *phpVersion, bool &out)
{
	if (EXPECTED(isExactly(phpVersion, PT_CLASS_PHP_VERSION))) {
		zval *versionId = declaredSlot(pt_pv_version_id_site, phpVersion, PT_LC("versionId"));
		if (EXPECTED(Z_TYPE_P(versionId) == IS_LONG)) {
			out = Z_LVAL_P(versionId) >= 80400;
			return true;
		}
	}
	return callBool(pt_pv_supports_property_hooks_site, phpVersion, PT_LC("supportspropertyhooks"), 0, NULL, out);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_property_hook_throw_points_resolver()
{
	reg::Class cls("PHPStan\\Analyser\\PropertyHookThrowPointsResolver");
	ptdecl::PropertyHookThrowPointsResolver::declareClass(cls);
	ptdecl::PropertyHookThrowPointsResolver::declareProperties(cls);

	/* the real parameter types: the DI container autowires the service by
	 * reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool implicitThrows;
		if (!zp::parse<zp::Bool>(execute_data, implicitThrows)) RETURN_THROWS();
		PropertyHookThrowPointsResolver(Z_OBJ_P(ZEND_THIS)).construct(implicitThrows);
	});

	cls.method(sigs::getThrowPointsFromPropertyHook, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *propertyFetch, *propertyReflection;
		zend_string *hookName;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Str>(execute_data, scope, propertyFetch, propertyReflection, hookName)) RETURN_THROWS();
		PT_RETURN_VAL(PropertyHookThrowPointsResolver(Z_OBJ_P(ZEND_THIS)).getThrowPointsFromPropertyHook(scope, propertyFetch, propertyReflection, hookName));
	});

	cls.shadow(&pt_ce_property_hook_throw_points_resolver);
}

/* }}} */
