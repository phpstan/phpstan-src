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
 * The reflection getters it asks — PhpPropertyReflection::getDeclaringClass()
 * / hasHook() / isPrivate() / isFinal() / getHook() and
 * PhpMethodFromParserNodeReflection::isPropertyHook() /
 * getHookedPropertyName() — only return a declared slot of their final
 * classes, so the slots are read in place (a pt_property_site each); the
 * hook's getThrowType() stays a call through a cached method site.
 *
 * The file also carries the property-reflection readers the fetch handlers
 * share (pt_property_reflection_*): the getters of the final
 * ResolvedPropertyReflection / ChangedTypePropertyReflection /
 * PhpPropertyReflection chain that only return a slot (or a filled memo),
 * read in place, and the method through a cached site otherwise.
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

pt_property_site pt_phtpr_prop_declaring_class_site;
pt_property_site pt_phtpr_prop_get_hook_site;
pt_property_site pt_phtpr_prop_set_hook_site;
pt_property_site pt_phtpr_prop_private_site;
pt_property_site pt_phtpr_prop_is_final_site;
pt_property_site pt_phtpr_method_hook_for_property_site;
pt_property_site pt_phtpr_fetch_var_site;
pt_property_site pt_phtpr_fetch_name_site;
pt_property_site pt_phtpr_variable_name_site;
pt_property_site pt_phtpr_identifier_name_site;
pt_method_site pt_phtpr_get_throw_type_site;
pt_method_site pt_phtpr_get_declaring_class_site;
pt_method_site pt_phtpr_has_hook_site;
pt_method_site pt_phtpr_is_private_site;
pt_method_site pt_phtpr_is_final_site;
pt_method_site pt_phtpr_get_hook_site;
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

/* {{{ PhpPropertyReflection getters (the slot of exactly the final class, the
 * method otherwise) */

zv::Val phpPropertyGetDeclaringClass(zval *reflection)
{
	if (EXPECTED(isExactly(reflection, PT_CLASS_PHP_PROPERTY_REFLECTION))) {
		zval *slot = declaredSlot(pt_phtpr_prop_declaring_class_site, reflection, PT_LC("declaringClass"));
		if (EXPECTED(Z_TYPE_P(slot) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(slot));
	}
	return pt_call_method_cached(pt_phtpr_get_declaring_class_site, Z_OBJ_P(reflection), PT_LC("getdeclaringclass"), 0, NULL);
}

/* $reflection->hasHook($hookType): `$hookType === 'get' ? $this->getHook !==
 * null : $this->setHook !== null` */
[[nodiscard]] bool phpPropertyHasHook(zval *reflection, zend_string *hookType, bool &out)
{
	if (EXPECTED(isExactly(reflection, PT_CLASS_PHP_PROPERTY_REFLECTION))) {
		bool get = zend_string_equals_literal(hookType, "get");
		zval *slot = get
			? declaredSlot(pt_phtpr_prop_get_hook_site, reflection, PT_LC("getHook"))
			: declaredSlot(pt_phtpr_prop_set_hook_site, reflection, PT_LC("setHook"));
		if (EXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) {
			out = Z_TYPE_P(slot) != IS_NULL;
			return true;
		}
	}
	zval hookTypeZv;
	ZVAL_STR(&hookTypeZv, hookType);
	return callBool(pt_phtpr_has_hook_site, reflection, PT_LC("hashook"), 1, &hookTypeZv, out);
}

[[nodiscard]] bool phpPropertyIsPrivate(zval *reflection, bool &out)
{
	if (EXPECTED(isExactly(reflection, PT_CLASS_PHP_PROPERTY_REFLECTION))) {
		zval *slot = declaredSlot(pt_phtpr_prop_private_site, reflection, PT_LC("private"));
		if (EXPECTED(Z_TYPE_P(slot) == IS_TRUE || Z_TYPE_P(slot) == IS_FALSE)) {
			out = Z_TYPE_P(slot) == IS_TRUE;
			return true;
		}
	}
	return callBool(pt_phtpr_is_private_site, reflection, PT_LC("isprivate"), 0, NULL, out);
}

/* $reflection->isFinal()->yes(): TrinaryLogic::createFromBoolean($this->isFinal) */
[[nodiscard]] bool phpPropertyIsFinalYes(zval *reflection, bool &out)
{
	if (EXPECTED(isExactly(reflection, PT_CLASS_PHP_PROPERTY_REFLECTION))) {
		zval *slot = declaredSlot(pt_phtpr_prop_is_final_site, reflection, PT_LC("isFinal"));
		if (EXPECTED(Z_TYPE_P(slot) == IS_TRUE || Z_TYPE_P(slot) == IS_FALSE)) {
			out = Z_TYPE_P(slot) == IS_TRUE;
			return true;
		}
	}
	zv::Val isFinal = pt_call_method_cached(pt_phtpr_is_final_site, Z_OBJ_P(reflection), PT_LC("isfinal"), 0, NULL);
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
	if (EXPECTED(isExactly(reflection, PT_CLASS_PHP_PROPERTY_REFLECTION))) {
		bool get = zend_string_equals_literal(hookType, "get");
		zval *slot = get
			? declaredSlot(pt_phtpr_prop_get_hook_site, reflection, PT_LC("getHook"))
			: declaredSlot(pt_phtpr_prop_set_hook_site, reflection, PT_LC("setHook"));
		if (EXPECTED(Z_TYPE_P(slot) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(slot));
	}
	zval hookTypeZv;
	ZVAL_STR(&hookTypeZv, hookType);
	return pt_call_method_cached(pt_phtpr_get_hook_site, Z_OBJ_P(reflection), PT_LC("gethook"), 1, &hookTypeZv);
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
		zv::Val throwType = pt_call_method_cached(pt_phtpr_get_throw_type_site, Z_OBJ_P(getHook.raw()), PT_LC("getthrowtype"), 0, NULL);
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
	zend_class_entry *phpPropertyReflectionCe = pt_class(PT_CLASS_PHP_PROPERTY_REFLECTION);
	if (UNEXPECTED(phpPropertyReflectionCe == NULL)) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_property_hook_throw_points_resolver && Z_TYPE_P(propertyReflection) == IS_OBJECT && instanceof_function(Z_OBJCE_P(propertyReflection), phpPropertyReflectionCe))) {
		return PropertyHookThrowPointsResolver(Z_OBJ_P(resolver)).getThrowPointsFromPropertyHook(scope, propertyFetch, propertyReflection, hookName);
	}
	zv::Args argv{scope, propertyFetch, propertyReflection, hookName};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("getthrowpointsfrompropertyhook"), 4, argv);
}

/* {{{ the property-reflection readers */

namespace {

pt_property_site pt_prr_resolved_reflection_site;
pt_property_site pt_prr_resolved_readable_type_site;
pt_property_site pt_prr_resolved_writable_type_site;
pt_property_site pt_prr_changed_declaring_class_site;
pt_property_site pt_prr_changed_reflection_site;
pt_property_site pt_prr_changed_readable_type_site;
pt_property_site pt_prr_changed_writable_type_site;
pt_property_site pt_prr_changed_native_type_site;
pt_property_site pt_prr_php_native_type_site;
pt_property_site pt_prr_php_readable_type_site;
pt_method_site pt_prr_get_declaring_class_site;
pt_method_site pt_prr_has_native_type_site;
pt_method_site pt_prr_get_native_type_site;
pt_method_site pt_prr_get_readable_type_site;
pt_method_site pt_prr_get_writable_type_site;

/* the wrapped reflection a getter of the wrapper only forwards to (borrowed),
 * NULL for any other class */
zval *forwardedReflection(zval *reflection, bool resolved, bool changedType)
{
	if (resolved && isExactly(reflection, PT_CLASS_RESOLVED_PROPERTY_REFLECTION)) {
		zval *inner = declaredSlot(pt_prr_resolved_reflection_site, reflection, PT_LC("reflection"));
		return Z_TYPE_P(inner) == IS_OBJECT ? inner : NULL;
	}
	if (changedType && isExactly(reflection, PT_CLASS_CHANGED_TYPE_PROPERTY_REFLECTION)) {
		zval *inner = declaredSlot(pt_prr_changed_reflection_site, reflection, PT_LC("reflection"));
		return Z_TYPE_P(inner) == IS_OBJECT ? inner : NULL;
	}
	return NULL;
}

/* a slot holding an object (a set memo), NULL otherwise */
inline zval *objectSlot(pt_property_site &site, zval *object, const char *name, size_t len)
{
	zval *slot = declaredSlot(site, object, name, len);
	return Z_TYPE_P(slot) == IS_OBJECT ? slot : NULL;
}

} // namespace

zv::Val pt_property_reflection_get_declaring_class(zval *reflection)
{
	for (;;) {
		zval *inner = forwardedReflection(reflection, true, false);
		if (inner == NULL) break;
		reflection = inner;
	}
	if (isExactly(reflection, PT_CLASS_CHANGED_TYPE_PROPERTY_REFLECTION)) {
		zval *slot = objectSlot(pt_prr_changed_declaring_class_site, reflection, PT_LC("declaringClass"));
		if (EXPECTED(slot != NULL)) return zv::Val::copyOf(zv::Ref(slot));
	}
	if (isExactly(reflection, PT_CLASS_PHP_PROPERTY_REFLECTION)) return phpPropertyGetDeclaringClass(reflection);
	return pt_call_method_cached(pt_prr_get_declaring_class_site, Z_OBJ_P(reflection), PT_LC("getdeclaringclass"), 0, NULL);
}

bool pt_property_reflection_has_native_type(zval *reflection, bool &out)
{
	for (;;) {
		zval *inner = forwardedReflection(reflection, true, true);
		if (inner == NULL) break;
		reflection = inner;
	}
	if (isExactly(reflection, PT_CLASS_PHP_PROPERTY_REFLECTION)) {
		/* !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed() */
		zval *nativeType = objectSlot(pt_prr_php_native_type_site, reflection, PT_LC("nativeType"));
		if (EXPECTED(nativeType != NULL)) {
			if (!instanceof_function(Z_OBJCE_P(nativeType), pt_ce_mixed_type)) {
				out = true;
				return true;
			}
			zv::Val explicitMixed = pt_type_call(Z_OBJ_P(nativeType), PT_LC("isexplicitmixed"), 0, NULL);
			if (UNEXPECTED(explicitMixed.isUndef())) return false;
			out = zend_is_true(explicitMixed.raw());
			return true;
		}
	}
	return callBool(pt_prr_has_native_type_site, reflection, PT_LC("hasnativetype"), 0, NULL, out);
}

zv::Val pt_property_reflection_get_native_type(zval *reflection)
{
	for (;;) {
		zval *inner = forwardedReflection(reflection, true, false);
		if (inner == NULL) break;
		reflection = inner;
	}
	if (isExactly(reflection, PT_CLASS_CHANGED_TYPE_PROPERTY_REFLECTION)) {
		zval *slot = objectSlot(pt_prr_changed_native_type_site, reflection, PT_LC("nativeType"));
		if (EXPECTED(slot != NULL)) return zv::Val::copyOf(zv::Ref(slot));
	} else if (isExactly(reflection, PT_CLASS_PHP_PROPERTY_REFLECTION)) {
		zval *slot = objectSlot(pt_prr_php_native_type_site, reflection, PT_LC("nativeType"));
		if (EXPECTED(slot != NULL)) return zv::Val::copyOf(zv::Ref(slot));
	}
	return pt_call_method_cached(pt_prr_get_native_type_site, Z_OBJ_P(reflection), PT_LC("getnativetype"), 0, NULL);
}

zv::Val pt_property_reflection_get_readable_type(zval *reflection)
{
	zval *slot = NULL;
	if (isExactly(reflection, PT_CLASS_RESOLVED_PROPERTY_REFLECTION)) {
		slot = objectSlot(pt_prr_resolved_readable_type_site, reflection, PT_LC("readableType"));
	} else if (isExactly(reflection, PT_CLASS_CHANGED_TYPE_PROPERTY_REFLECTION)) {
		slot = objectSlot(pt_prr_changed_readable_type_site, reflection, PT_LC("readableType"));
	} else if (isExactly(reflection, PT_CLASS_PHP_PROPERTY_REFLECTION)) {
		slot = objectSlot(pt_prr_php_readable_type_site, reflection, PT_LC("readableType"));
	}
	if (EXPECTED(slot != NULL)) return zv::Val::copyOf(zv::Ref(slot));
	return pt_call_method_cached(pt_prr_get_readable_type_site, Z_OBJ_P(reflection), PT_LC("getreadabletype"), 0, NULL);
}

zv::Val pt_property_reflection_get_writable_type(zval *reflection)
{
	zval *slot = NULL;
	if (isExactly(reflection, PT_CLASS_RESOLVED_PROPERTY_REFLECTION)) {
		slot = objectSlot(pt_prr_resolved_writable_type_site, reflection, PT_LC("writableType"));
	} else if (isExactly(reflection, PT_CLASS_CHANGED_TYPE_PROPERTY_REFLECTION)) {
		slot = objectSlot(pt_prr_changed_writable_type_site, reflection, PT_LC("writableType"));
	}
	if (EXPECTED(slot != NULL)) return zv::Val::copyOf(zv::Ref(slot));
	return pt_call_method_cached(pt_prr_get_writable_type_site, Z_OBJ_P(reflection), PT_LC("getwritabletype"), 0, NULL);
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
