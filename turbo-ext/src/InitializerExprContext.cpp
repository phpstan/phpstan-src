/*
 * PHPStanTurbo\InitializerExprContext — native implementation of
 * PHPStan\Reflection\InitializerExprContext.
 *
 * The context a constant expression is priced in (a default value, a
 * constant's value, an attribute argument): a final value class over seven
 * nullable strings with a private constructor, built by the static
 * factories. fromScope() is asked ~90K times per self-analysis by the
 * handlers and MutatingScope; it asks the scope through MutatingScope's
 * direct entries and the function reflection (a PHP
 * Php*FromParserNodeReflection) through cached method sites.
 * fromReflectionParameter() reads a method's BetterReflection adapters
 * through the readers of BetterReflectionAccess.cpp, a function's through
 * cached sites. Native callers use the pt_initializer_expr_context_*
 * entries (support.h) and the inline slot readers of AnalyserValues.h.
 */

#include "support.h"
#include "generated/InitializerExprContext.h"

namespace slots = ptdecl::InitializerExprContext::slot;
namespace sigs = ptdecl::InitializerExprContext::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

#include "zend_exceptions.h"

zend_class_entry *pt_ce_initializer_expr_context = nullptr;

namespace {

/* '{closure}' as a permanent interned string */
zend_string *pt_iec_closure = nullptr;

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_iec_function_get_name_site;
pt_method_site pt_iec_function_get_declaring_class_site;
pt_method_site pt_iec_function_is_property_hook_site;
pt_method_site pt_iec_function_get_hooked_property_name_site;
pt_method_site pt_iec_parameter_get_declaring_function_site;
pt_method_site pt_iec_declaring_function_get_file_name_site;
pt_method_site pt_iec_declaring_function_get_name_site;
pt_method_site pt_iec_constant_get_file_name_site;
pt_method_site pt_iec_constant_get_namespace_name_site;

/* $object->method() through a cached site; the engine's "Call to a member
 * function" Error for a non-object; UNDEF = pending exception */
zv::Val callOn(pt_method_site &site, zval *object, const char *lcname, size_t len, const char *method)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, 0, NULL);
}

/* {{{ $node->name->toString() / $node->namespacedName->toString() of the
 * php-parser Identifier / Name: the declared $name slot while the object's
 * class inherits the vendored toString() (both return `$this->name`), the
 * method otherwise */

struct NameToStringSite
{
	pt_method_site method;
	pt_property_site name;
};

NameToStringSite pt_iec_identifier_to_string_site;
NameToStringSite pt_iec_name_to_string_site;

zv::Val nameToString(NameToStringSite &site, zval *name)
{
	if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(name));
		return zv::Val();
	}
	zend_object *object = Z_OBJ_P(name);
	if (UNEXPECTED(site.method.ce != object->ce || site.method.generation != pt_engine_generation || site.method.fn == NULL)) {
		zend_function *fn = pt_find_method(object->ce, PT_LC("tostring"));
		if (UNEXPECTED(fn == NULL)) return zv::Val();
		site.method = { object->ce, fn, pt_engine_generation };
	}
	zend_class_entry *declaring = site.method.fn->common.scope;
	if (EXPECTED(declaring != NULL && (declaring == pt_class_loaded(PT_CLASS_IDENTIFIER) || declaring == pt_class_loaded(PT_CLASS_NAME)))) {
		zval *slot = pt_property_cached(site.name, object, PT_LC("name"));
		if (EXPECTED(slot != NULL)) {
			ZVAL_DEREF(slot);
			if (EXPECTED(Z_TYPE_P(slot) == IS_STRING)) return zv::Val::copyOf(zv::Ref(slot));
		}
	}
	return pt_call_method_cached(site.method, object, PT_LC("tostring"), 0, NULL);
}

/* }}} */

/* $node->$property of a declared public property (the typed-property read
 * Error for an uninitialized one); NULL = pending exception */
zval *nodeProperty(pt_property_site &site, zend_object *node, const char *name, size_t len, zval &rv)
{
	zval *slot = pt_property_cached(site, node, name, len);
	if (EXPECTED(slot != NULL)) {
		ZVAL_DEREF(slot);
		if (EXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) return slot;
	}
	/* the engine's read: the uninitialized-property Error, or an undeclared
	 * property's warning and null */
	zval *value = zend_read_property(node->ce, node, name, len, 0, &rv);
	if (UNEXPECTED(EG(exception))) return NULL;
	ZVAL_DEREF(value);
	return value;
}

pt_property_site pt_iec_namespaced_name_site;
pt_property_site pt_iec_function_name_site;

/* }}} */

/* the twin's `?string` constructor parameter check for a value that did not
 * come typed; false = TypeError raised */
[[nodiscard]] bool requireNullableString(zval *value, uint32_t argNum, const char *name)
{
	if (EXPECTED(value == NULL || Z_TYPE_P(value) == IS_STRING || Z_TYPE_P(value) == IS_NULL)) return true;
	zend_type_error("PHPStan\\Reflection\\InitializerExprContext::__construct(): Argument #%u ($%s) must be of type ?string, %s given", argNum, name, zend_zval_value_name(value));
	return false;
}

/* the `string $name` parameter check of parseNamespace(); false = TypeError raised */
[[nodiscard]] bool requireParseNamespaceString(zval *value)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) return true;
	zend_type_error("PHPStan\\Reflection\\InitializerExprContext::parseNamespace(): Argument #1 ($name) must be of type string, %s given", zend_zval_value_name(value));
	return false;
}

/* sprintf('%s::%s', $a, $b) of two strings */
zv::Val joinDoubleColon(zend_string *left, zend_string *right)
{
	return zv::Val::adoptString(zend_string_concat3(ZSTR_VAL(left), ZSTR_LEN(left), "::", 2, ZSTR_VAL(right), ZSTR_LEN(right)));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\InitializerExprContext; UNDEF = pending exception. */
class InitializerExprContext
{
public:
	explicit InitializerExprContext(zend_object *self) : self(self) {}

	/* the private constructor's body: the seven promoted slots (borrowed;
	 * NULL = null) */
	void construct(zval *file, zval *namespace_, zval *className, zval *traitName, zval *function, zval *method, zval *property) const
	{
		write(slots::file, file);
		write(slots::namespace_, namespace_);
		write(slots::className, className);
		write(slots::traitName, traitName);
		write(slots::function, function);
		write(slots::method, method);
		write(slots::property, property);
	}

	/* new self(...) with values typed by the caller (borrowed; NULL / IS_NULL = null) */
	static zv::Val create(zval *file, zval *namespace_, zval *className, zval *traitName, zval *function, zval *method, zval *property)
	{
		zval object;
		object_init_ex(&object, pt_ce_initializer_expr_context);
		zv::Val context = zv::Val::adopt(object);
		InitializerExprContext(Z_OBJ(object)).construct(file, namespace_, className, traitName, function, method, property);
		return context;
	}

	/* new self(...) with the constructor's `?string` checks, for values
	 * read from untyped sources */
	static zv::Val createChecked(zval *file, zval *namespace_, zval *className, zval *traitName, zval *function, zval *method, zval *property)
	{
		if (UNEXPECTED(!requireNullableString(file, 1, "file") || !requireNullableString(namespace_, 2, "namespace") || !requireNullableString(className, 3, "className") || !requireNullableString(traitName, 4, "traitName") || !requireNullableString(function, 5, "function") || !requireNullableString(method, 6, "method") || !requireNullableString(property, 7, "property"))) {
			return zv::Val();
		}
		return create(file, namespace_, className, traitName, function, method, property);
	}

	/* Mirrors parseNamespace(): null for a name without a backslash
	 * (IS_NULL out), false = pending ShouldNotHappenException */
	[[nodiscard]] static bool parseNamespace(zend_string *name, zv::Val &out)
	{
		const char *last = (const char *) zend_memrchr(ZSTR_VAL(name), '\\', ZSTR_LEN(name));
		if (last == NULL) {
			out = zv::Val::null();
			return true;
		}
		size_t length = (size_t) (last - ZSTR_VAL(name));
		if (UNEXPECTED(length == 0)) {
			zval message;
			ZVAL_STRINGL(&message, "Namespace cannot be empty.", sizeof("Namespace cannot be empty.") - 1);
			zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 1, &message);
			zval_ptr_dtor(&message);
			if (EXPECTED(!exception.isUndef())) {
				zval raw = exception.take();
				zend_throw_exception_object(&raw);
			}
			return false;
		}
		out = zv::Val::string(ZSTR_VAL(name), length);
		return true;
	}

	/* Mirrors fromScope(). */
	static zv::Val fromScope(zval *scopeZv)
	{
		zend_object *scope = Z_OBJ_P(scopeZv);
		zv::Val function = pt_mutating_scope_get_function(scope);
		if (UNEXPECTED(function.isUndef())) return zv::Val();

		zv::Val file = pt_mutating_scope_get_file(scope);
		if (UNEXPECTED(file.isUndef())) return zv::Val();
		bool inTrait;
		if (UNEXPECTED(!pt_mutating_scope_is_in_trait(scope, inTrait))) return zv::Val();
		if (inTrait) {
			zv::Val traitReflection = pt_mutating_scope_get_trait_reflection(scope);
			if (UNEXPECTED(traitReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(traitReflection.raw()) != IS_OBJECT)) return nonObjectCall("getFileName", traitReflection.raw());
			zv::Val traitFileName = pt_class_reflection_get_file_name(Z_OBJ_P(traitReflection.raw()));
			if (UNEXPECTED(traitFileName.isUndef())) return zv::Val();
			if (Z_TYPE_P(traitFileName.raw()) != IS_NULL) file = std::move(traitFileName);
		}

		zv::Val namespace_ = pt_mutating_scope_get_namespace(scope);
		if (UNEXPECTED(namespace_.isUndef())) return zv::Val();

		bool inClass;
		if (UNEXPECTED(!pt_mutating_scope_is_in_class(scope, inClass))) return zv::Val();
		zv::Val className = zv::Val::null();
		if (inClass) {
			zv::Val classReflection = pt_mutating_scope_get_class_reflection(scope);
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) return nonObjectCall("getName", classReflection.raw());
			className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(className.isUndef())) return zv::Val();
		}

		if (UNEXPECTED(!pt_mutating_scope_is_in_trait(scope, inTrait))) return zv::Val();
		zv::Val traitName = zv::Val::null();
		if (inTrait) {
			zv::Val traitReflection = pt_mutating_scope_get_trait_reflection(scope);
			if (UNEXPECTED(traitReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(traitReflection.raw()) != IS_OBJECT)) return nonObjectCall("getName", traitReflection.raw());
			traitName = pt_class_reflection_get_name(Z_OBJ_P(traitReflection.raw()));
			if (UNEXPECTED(traitName.isUndef())) return zv::Val();
		}

		zval closure;
		ZVAL_INTERNED_STR(&closure, pt_iec_closure);
		bool inAnonymousFunction;
		if (UNEXPECTED(!pt_mutating_scope_is_in_anonymous_function(scope, inAnonymousFunction))) return zv::Val();
		zv::Val functionName = zv::Val::null();
		if (!inAnonymousFunction && Z_TYPE_P(function.raw()) != IS_NULL) {
			functionName = callOn(pt_iec_function_get_name_site, function.raw(), PT_LC("getname"), "getName");
			if (UNEXPECTED(functionName.isUndef())) return zv::Val();
		}

		if (UNEXPECTED(!pt_mutating_scope_is_in_anonymous_function(scope, inAnonymousFunction))) return zv::Val();
		zv::Val method = zv::Val::null();
		bool isObject = Z_TYPE_P(function.raw()) == IS_OBJECT;
		if (!inAnonymousFunction && isObject) {
			zend_class_entry *methodReflectionCe = pt_class_loaded(PT_CLASS_METHOD_REFLECTION);
			if (UNEXPECTED(EG(exception))) return zv::Val();
			if (methodReflectionCe != NULL && instanceof_function(Z_OBJCE_P(function.raw()), methodReflectionCe)) {
				zv::Val declaringClass = callOn(pt_iec_function_get_declaring_class_site, function.raw(), PT_LC("getdeclaringclass"), "getDeclaringClass");
				if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(declaringClass.raw()) != IS_OBJECT)) return nonObjectCall("getName", declaringClass.raw());
				zv::Val declaringName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
				if (UNEXPECTED(declaringName.isUndef())) return zv::Val();
				zv::Val name = callOn(pt_iec_function_get_name_site, function.raw(), PT_LC("getname"), "getName");
				if (UNEXPECTED(name.isUndef())) return zv::Val();
				zend_string *left = zval_try_get_string(declaringName.raw());
				if (UNEXPECTED(left == NULL)) return zv::Val();
				zend_string *right = zval_try_get_string(name.raw());
				if (UNEXPECTED(right == NULL)) {
					zend_string_release(left);
					return zv::Val();
				}
				method = joinDoubleColon(left, right);
				zend_string_release(left);
				zend_string_release(right);
			} else {
				zend_class_entry *functionReflectionCe = pt_class_loaded(PT_CLASS_FUNCTION_REFLECTION);
				if (UNEXPECTED(EG(exception))) return zv::Val();
				if (functionReflectionCe != NULL && instanceof_function(Z_OBJCE_P(function.raw()), functionReflectionCe)) {
					method = callOn(pt_iec_function_get_name_site, function.raw(), PT_LC("getname"), "getName");
					if (UNEXPECTED(method.isUndef())) return zv::Val();
				}
			}
		}

		zv::Val property = zv::Val::null();
		if (isObject) {
			zend_class_entry *hookCe = pt_class_loaded(PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION);
			if (UNEXPECTED(EG(exception))) return zv::Val();
			if (hookCe != NULL && instanceof_function(Z_OBJCE_P(function.raw()), hookCe)) {
				zv::Val isPropertyHook = callOn(pt_iec_function_is_property_hook_site, function.raw(), PT_LC("ispropertyhook"), "isPropertyHook");
				if (UNEXPECTED(isPropertyHook.isUndef())) return zv::Val();
				if (zend_is_true(isPropertyHook.raw())) {
					property = callOn(pt_iec_function_get_hooked_property_name_site, function.raw(), PT_LC("gethookedpropertyname"), "getHookedPropertyName");
					if (UNEXPECTED(property.isUndef())) return zv::Val();
				}
			}
		}

		return createChecked(file.raw(), namespace_.raw(), className.raw(), traitName.raw(), inAnonymousFunction ? &closure : functionName.raw(), inAnonymousFunction ? &closure : method.raw(), property.raw());
	}

	/* Mirrors fromClassReflection(). */
	static zv::Val fromClassReflection(zval *classReflection)
	{
		zv::Val name = pt_class_reflection_get_name(Z_OBJ_P(classReflection));
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val fileName = pt_class_reflection_get_file_name(Z_OBJ_P(classReflection));
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		return fromClassChecked(name.raw(), fileName.raw(), "fromClass");
	}

	/* Mirrors fromClass() ($className a string, $fileName a string or null). */
	static zv::Val fromClass(zend_string *className, zval *fileName)
	{
		zv::Val namespace_;
		if (UNEXPECTED(!parseNamespace(className, namespace_))) return zv::Val();
		zval classNameZv;
		ZVAL_STR(&classNameZv, className);
		return create(fileName, namespace_.raw(), &classNameZv, NULL, NULL, NULL, NULL);
	}

	/* Mirrors fromFunction(). */
	static zv::Val fromFunction(zend_string *functionName, zval *fileName)
	{
		zv::Val namespace_;
		if (UNEXPECTED(!parseNamespace(functionName, namespace_))) return zv::Val();
		zval functionNameZv;
		ZVAL_STR(&functionNameZv, functionName);
		return create(fileName, namespace_.raw(), NULL, NULL, &functionNameZv, &functionNameZv, NULL);
	}

	/* Mirrors fromClassMethod(). */
	static zv::Val fromClassMethod(zend_string *className, zval *traitName, zend_string *methodName, zval *fileName)
	{
		zv::Val namespace_;
		if (UNEXPECTED(!parseNamespace(className, namespace_))) return zv::Val();
		zval classNameZv, methodNameZv;
		ZVAL_STR(&classNameZv, className);
		ZVAL_STR(&methodNameZv, methodName);
		zv::Val method = joinDoubleColon(className, methodName);
		return create(fileName, namespace_.raw(), &classNameZv, traitName, &methodNameZv, method.raw(), NULL);
	}

	/* Mirrors fromReflectionParameter(). */
	static zv::Val fromReflectionParameter(zval *parameter)
	{
		zv::Val declaringFunction = callOn(pt_iec_parameter_get_declaring_function_site, parameter, PT_LC("getdeclaringfunction"), "getDeclaringFunction");
		if (UNEXPECTED(declaringFunction.isUndef())) return zv::Val();
		zval *functionZv = declaringFunction.raw();

		bool isFunction = false;
		if (Z_TYPE_P(functionZv) == IS_OBJECT) {
			zend_class_entry *reflectionFunctionCe = pt_class_loaded(PT_CLASS_ADAPTER_REFLECTION_FUNCTION);
			if (UNEXPECTED(EG(exception))) return zv::Val();
			isFunction = reflectionFunctionCe != NULL && instanceof_function(Z_OBJCE_P(functionZv), reflectionFunctionCe);
		}
		if (isFunction) {
			zv::Val file = callOn(pt_iec_declaring_function_get_file_name_site, functionZv, PT_LC("getfilename"), "getFileName");
			if (UNEXPECTED(file.isUndef())) return zv::Val();
			zv::Val name = callOn(pt_iec_declaring_function_get_name_site, functionZv, PT_LC("getname"), "getName");
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			if (UNEXPECTED(!requireParseNamespaceString(name.raw()))) return zv::Val();
			zv::Val namespace_;
			if (UNEXPECTED(!parseNamespace(Z_STR_P(name.raw()), namespace_))) return zv::Val();
			zv::Val functionName = callOn(pt_iec_declaring_function_get_name_site, functionZv, PT_LC("getname"), "getName");
			if (UNEXPECTED(functionName.isUndef())) return zv::Val();
			zv::Val methodName = callOn(pt_iec_declaring_function_get_name_site, functionZv, PT_LC("getname"), "getName");
			if (UNEXPECTED(methodName.isUndef())) return zv::Val();
			return createChecked(Z_TYPE_P(file.raw()) == IS_FALSE ? NULL : file.raw(), namespace_.raw(), NULL, NULL, functionName.raw(), methodName.raw(), NULL);
		}

		zv::Val file = callOn(pt_iec_declaring_function_get_file_name_site, functionZv, PT_LC("getfilename"), "getFileName");
		if (UNEXPECTED(file.isUndef())) return zv::Val();
		zv::Val betterReflection = pt_reflection_adapter_get_better_reflection(functionZv);
		if (UNEXPECTED(betterReflection.isUndef())) return zv::Val();

		/* self::parseNamespace($betterReflection->getDeclaringClass()->getName()) */
		zv::Val betterClass = pt_better_reflection_member_get_declaring_class(betterReflection.raw());
		if (UNEXPECTED(betterClass.isUndef())) return zv::Val();
		zv::Val betterClassName = pt_better_reflection_class_get_name(betterClass.raw());
		if (UNEXPECTED(betterClassName.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireParseNamespaceString(betterClassName.raw()))) return zv::Val();
		zv::Val namespace_;
		if (UNEXPECTED(!parseNamespace(Z_STR_P(betterClassName.raw()), namespace_))) return zv::Val();

		/* $declaringFunction->getDeclaringClass()->getName() */
		zv::Val className = pt_member_adapter_get_declaring_class_name(functionZv);
		if (UNEXPECTED(className.isUndef())) return zv::Val();

		/* $betterReflection->getDeclaringClass()->isTrait() ? $betterReflection->getDeclaringClass()->getName() : null */
		betterClass = pt_better_reflection_member_get_declaring_class(betterReflection.raw());
		if (UNEXPECTED(betterClass.isUndef())) return zv::Val();
		bool isTrait;
		if (UNEXPECTED(!pt_better_reflection_class_is_trait(betterClass.raw(), isTrait))) return zv::Val();
		zv::Val traitName = zv::Val::null();
		if (isTrait) {
			betterClass = pt_better_reflection_member_get_declaring_class(betterReflection.raw());
			if (UNEXPECTED(betterClass.isUndef())) return zv::Val();
			traitName = pt_better_reflection_class_get_name(betterClass.raw());
			if (UNEXPECTED(traitName.isUndef())) return zv::Val();
		}

		zv::Val functionName = pt_method_adapter_get_name(functionZv);
		if (UNEXPECTED(functionName.isUndef())) return zv::Val();

		/* sprintf('%s::%s', $declaringFunction->getDeclaringClass()->getName(), $declaringFunction->getName()) */
		zv::Val methodClassName = pt_member_adapter_get_declaring_class_name(functionZv);
		if (UNEXPECTED(methodClassName.isUndef())) return zv::Val();
		zv::Val methodName = pt_method_adapter_get_name(functionZv);
		if (UNEXPECTED(methodName.isUndef())) return zv::Val();
		zv::Val method = sprintfDoubleColon(methodClassName.raw(), methodName.raw());
		if (UNEXPECTED(method.isUndef())) return zv::Val();

		return createChecked(Z_TYPE_P(file.raw()) == IS_FALSE ? NULL : file.raw(), namespace_.raw(), className.raw(), traitName.raw(), functionName.raw(), method.raw(), NULL);
	}

	/* Mirrors fromStubParameter() ($className a string or null, $stubFile a
	 * string, $function a ClassMethod, Function_ or PropertyHook). */
	static zv::Val fromStubParameter(zval *className, zval *stubFile, zval *function)
	{
		zend_object *node = Z_OBJ_P(function);
		zend_class_entry *functionCe = pt_class_loaded(PT_CLASS_FUNCTION_STMT);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		zend_class_entry *classMethodCe = pt_class_loaded(PT_CLASS_CLASS_METHOD_STMT);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		zend_class_entry *propertyHookCe = pt_class_loaded(PT_CLASS_PROPERTY_HOOK);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		bool isFunction = functionCe != NULL && instanceof_function(node->ce, functionCe);
		bool isClassMethod = classMethodCe != NULL && instanceof_function(node->ce, classMethodCe);
		bool isPropertyHook = propertyHookCe != NULL && instanceof_function(node->ce, propertyHookCe);
		bool hasClassName = Z_TYPE_P(className) != IS_NULL;

		zv::Val namespace_ = zv::Val::null();
		if (hasClassName) {
			if (UNEXPECTED(!parseNamespace(Z_STR_P(className), namespace_))) return zv::Val();
		} else if (isFunction) {
			zval rv;
			zval *namespacedName = nodeProperty(pt_iec_namespaced_name_site, node, PT_LC("namespacedName"), rv);
			if (UNEXPECTED(namespacedName == NULL)) return zv::Val();
			if (Z_TYPE_P(namespacedName) != IS_NULL) {
				zv::Val name = nameToString(pt_iec_name_to_string_site, namespacedName);
				if (UNEXPECTED(name.isUndef())) return zv::Val();
				if (UNEXPECTED(!requireParseNamespaceString(name.raw()))) return zv::Val();
				if (UNEXPECTED(!parseNamespace(Z_STR_P(name.raw()), namespace_))) return zv::Val();
			}
		}

		zv::Val functionName = zv::Val::null();
		zv::Val propertyName = zv::Val::null();
		bool functionHandled = false;
		if (isFunction) {
			zval rv;
			zval *namespacedName = nodeProperty(pt_iec_namespaced_name_site, node, PT_LC("namespacedName"), rv);
			if (UNEXPECTED(namespacedName == NULL)) return zv::Val();
			if (Z_TYPE_P(namespacedName) != IS_NULL) {
				functionHandled = true;
				zval rv2;
				namespacedName = nodeProperty(pt_iec_namespaced_name_site, node, PT_LC("namespacedName"), rv2);
				if (UNEXPECTED(namespacedName == NULL)) return zv::Val();
				functionName = nameToString(pt_iec_name_to_string_site, namespacedName);
				if (UNEXPECTED(functionName.isUndef())) return zv::Val();
			}
		}
		if (!functionHandled) {
			if (isClassMethod) {
				zv::Val name = identifierName(node);
				if (UNEXPECTED(name.isUndef())) return zv::Val();
				functionName = std::move(name);
			} else if (isPropertyHook) {
				propertyName = pt_engine_node_get_attribute(node, PT_LC("propertyName"));
				if (UNEXPECTED(propertyName.isUndef())) return zv::Val();
				/* sprintf('$%s::%s', $propertyName, $function->name->toString()) */
				zv::Val hookName = identifierName(node);
				if (UNEXPECTED(hookName.isUndef())) return zv::Val();
				functionName = sprintfValues("$", propertyName.raw(), "::", hookName.raw());
				if (UNEXPECTED(functionName.isUndef())) return zv::Val();
			}
		}

		zv::Val methodName = zv::Val::null();
		if (isClassMethod && hasClassName) {
			zv::Val name = identifierName(node);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			methodName = sprintfDoubleColon(className, name.raw());
			if (UNEXPECTED(methodName.isUndef())) return zv::Val();
		} else if (isPropertyHook) {
			propertyName = pt_engine_node_get_attribute(node, PT_LC("propertyName"));
			if (UNEXPECTED(propertyName.isUndef())) return zv::Val();
			/* sprintf('%s::$%s::%s', $className, $propertyName, $function->name->toString()) */
			zv::Val hookName = identifierName(node);
			if (UNEXPECTED(hookName.isUndef())) return zv::Val();
			zv::Val prefix = sprintfValues("", className, "::$", propertyName.raw());
			if (UNEXPECTED(prefix.isUndef())) return zv::Val();
			methodName = sprintfValues("", prefix.raw(), "::", hookName.raw());
			if (UNEXPECTED(methodName.isUndef())) return zv::Val();
		} else if (isFunction) {
			zval rv;
			zval *namespacedName = nodeProperty(pt_iec_namespaced_name_site, node, PT_LC("namespacedName"), rv);
			if (UNEXPECTED(namespacedName == NULL)) return zv::Val();
			if (Z_TYPE_P(namespacedName) != IS_NULL) {
				zval rv2;
				namespacedName = nodeProperty(pt_iec_namespaced_name_site, node, PT_LC("namespacedName"), rv2);
				if (UNEXPECTED(namespacedName == NULL)) return zv::Val();
				methodName = nameToString(pt_iec_name_to_string_site, namespacedName);
				if (UNEXPECTED(methodName.isUndef())) return zv::Val();
			}
		}

		return createChecked(stubFile, namespace_.raw(), className, NULL, functionName.raw(), methodName.raw(), propertyName.raw());
	}

	/* Mirrors fromGlobalConstant(). */
	static zv::Val fromGlobalConstant(zval *constant)
	{
		zv::Val fileName = callOn(pt_iec_constant_get_file_name_site, constant, PT_LC("getfilename"), "getFileName");
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		zv::Val namespaceName = callOn(pt_iec_constant_get_namespace_name_site, constant, PT_LC("getnamespacename"), "getNamespaceName");
		if (UNEXPECTED(namespaceName.isUndef())) return zv::Val();
		return createChecked(fileName.raw(), namespaceName.raw(), NULL, NULL, NULL, NULL, NULL);
	}

	/* Mirrors createEmpty(). */
	static zv::Val createEmpty()
	{
		return create(NULL, NULL, NULL, NULL, NULL, NULL, NULL);
	}

	/* the getters: the slot (the typed-property Error for an instance whose
	 * constructor never ran) */
	zv::Val getFile() const { return get(slots::file, "file"); }
	zv::Val getClassName() const { return get(slots::className, "className"); }
	zv::Val getNamespace() const { return get(slots::namespace_, "namespace"); }
	zv::Val getTraitName() const { return get(slots::traitName, "traitName"); }
	zv::Val getFunction() const { return get(slots::function, "function"); }
	zv::Val getMethod() const { return get(slots::method, "method"); }
	zv::Val getProperty() const { return get(slots::property, "property"); }

private:
	zend_object *self;

	void write(uint32_t index, zval *value) const
	{
		if (value == NULL) {
			zval null = {};
			ZVAL_NULL(&null);
			pt_write_slot(self, index, &null);
			return;
		}
		pt_write_slot(self, index, value);
	}

	zv::Val get(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, pt_ce_initializer_expr_context, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}

	[[nodiscard]] static zv::Val nonObjectCall(const char *method, zval *value)
	{
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
		return zv::Val();
	}

	/* fromClass() of fromClassReflection(): the reflection's getters are
	 * typed, a foreign ClassReflection answering otherwise gets the
	 * parameter TypeError */
	static zv::Val fromClassChecked(zval *className, zval *fileName, const char *method)
	{
		if (UNEXPECTED(Z_TYPE_P(className) != IS_STRING)) {
			zend_type_error("PHPStan\\Reflection\\InitializerExprContext::%s(): Argument #1 ($className) must be of type string, %s given", method, zend_zval_value_name(className));
			return zv::Val();
		}
		if (UNEXPECTED(Z_TYPE_P(fileName) != IS_STRING && Z_TYPE_P(fileName) != IS_NULL)) {
			zend_type_error("PHPStan\\Reflection\\InitializerExprContext::%s(): Argument #2 ($fileName) must be of type ?string, %s given", method, zend_zval_value_name(fileName));
			return zv::Val();
		}
		return fromClass(Z_STR_P(className), fileName);
	}

	/* $function->name->toString() of a ClassMethod / PropertyHook */
	static zv::Val identifierName(zend_object *node)
	{
		zval rv;
		zval *name = nodeProperty(pt_iec_function_name_site, node, PT_LC("name"), rv);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return nameToString(pt_iec_identifier_to_string_site, name);
	}

	/* sprintf('%s::%s', $left, $right) of any two values */
	static zv::Val sprintfDoubleColon(zval *left, zval *right)
	{
		return sprintfValues("", left, "::", right);
	}

	/* sprintf('<prefix>%s<middle>%s', $first, $second): the values converted
	 * the way sprintf()'s %s converts them; UNDEF = pending exception */
	static zv::Val sprintfValues(const char *prefix, zval *first, const char *middle, zval *second)
	{
		zend_string *firstString = zval_try_get_string(first);
		if (UNEXPECTED(firstString == NULL)) return zv::Val();
		zend_string *secondString = zval_try_get_string(second);
		if (UNEXPECTED(secondString == NULL)) {
			zend_string_release(firstString);
			return zv::Val();
		}
		size_t prefixLength = strlen(prefix);
		size_t middleLength = strlen(middle);
		zend_string *result = zend_string_alloc(prefixLength + ZSTR_LEN(firstString) + middleLength + ZSTR_LEN(secondString), 0);
		char *cursor = ZSTR_VAL(result);
		memcpy(cursor, prefix, prefixLength);
		cursor += prefixLength;
		memcpy(cursor, ZSTR_VAL(firstString), ZSTR_LEN(firstString));
		cursor += ZSTR_LEN(firstString);
		memcpy(cursor, middle, middleLength);
		cursor += middleLength;
		memcpy(cursor, ZSTR_VAL(secondString), ZSTR_LEN(secondString));
		cursor += ZSTR_LEN(secondString);
		*cursor = '\0';
		zend_string_release(firstString);
		zend_string_release(secondString);
		return zv::Val::adoptString(result);
	}
};

} // namespace phpstanturbo

using phpstanturbo::InitializerExprContext;

/* {{{ direct entries (support.h) */

namespace {

#define PT_IEC_CLASS "PHPStan\\Reflection\\InitializerExprContext"

/* The class under the twin's real name: the native class in production;
 * under the prefixed activation of the differential tests (the native class
 * is PHPStanTurbo\InitializerExprContext) the PHP twin, which the PHP
 * collaborators the native callers hand the context to
 * (InitializerExprTypeResolver, AttributeReflectionFactory) declare as their
 * parameter type. Decided once per activated class entry; NULL = the native
 * class (or a pending exception when the twin cannot be loaded). */
zend_class_entry *realNameClass()
{
	static zend_class_entry *decidedFor = NULL;
	static bool realName = true;
	if (EXPECTED(decidedFor == pt_ce_initializer_expr_context)) {
		if (EXPECTED(realName)) return NULL;
	} else {
		decidedFor = pt_ce_initializer_expr_context;
		realName = pt_ce_initializer_expr_context == NULL || zend_string_equals_literal(pt_ce_initializer_expr_context->name, PT_IEC_CLASS);
		if (realName) return NULL;
	}
	zend_string *name = zend_string_init(ZEND_STRL(PT_IEC_CLASS), 0);
	zend_class_entry *twin = zend_lookup_class(name);
	zend_string_release(name);
	if (UNEXPECTED(twin == NULL) && !EG(exception)) {
		zend_throw_error(NULL, "Class \"%s\" not found", PT_IEC_CLASS);
	}
	return twin;
}

/* the twin's static factory by name under the prefix; handled = false for
 * the native class */
zv::Val onTwin(bool &handled, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zend_class_entry *twin = realNameClass();
	if (EXPECTED(twin == NULL)) {
		handled = UNEXPECTED(EG(exception) != NULL);
		return zv::Val();
	}
	handled = true;
	return pt_type_call_static_ce(twin, lcname, len, argc, argv);
}

/* a native caller's argument of the factory's parameter type, checked like
 * the twin's parameter (a string, or null when nullable); false = TypeError
 * raised */
[[nodiscard]] bool entryArgument(zval *value, bool nullable, const char *method, uint32_t argNum, const char *name)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_STRING || (nullable && Z_TYPE_P(value) == IS_NULL))) return true;
	zend_type_error(PT_IEC_CLASS "::%s(): Argument #%u ($%s) must be of type %s, %s given", method, argNum, name, nullable ? "?string" : "string", zend_zval_value_name(value));
	return false;
}

} // namespace

zv::Val pt_initializer_expr_context_from_scope(zval *scope)
{
	bool handled;
	zv::Val twin = onTwin(handled, PT_LC("fromscope"), 1, scope);
	if (UNEXPECTED(handled)) return twin;
	return InitializerExprContext::fromScope(scope);
}

zv::Val pt_initializer_expr_context_from_class_reflection(zval *classReflection)
{
	zend_class_entry *twin = realNameClass();
	if (EXPECTED(twin == NULL)) {
		if (UNEXPECTED(EG(exception))) return zv::Val();
		return InitializerExprContext::fromClassReflection(classReflection);
	}
	/* under the prefix the reflection may be the prefixed native class,
	 * which the twin's `ClassReflection` parameter refuses: its body,
	 * self::fromClass($classReflection->getName(), $classReflection->getFileName()),
	 * with the twin's fromClass() */
	zv::Val name = pt_class_reflection_get_name(Z_OBJ_P(classReflection));
	if (UNEXPECTED(name.isUndef())) return zv::Val();
	zv::Val fileName = pt_class_reflection_get_file_name(Z_OBJ_P(classReflection));
	if (UNEXPECTED(fileName.isUndef())) return zv::Val();
	zv::Args argv{name.raw(), fileName.raw()};
	return pt_type_call_static_ce(twin, PT_LC("fromclass"), 2, argv);
}

zv::Val pt_initializer_expr_context_from_class(zval *className, zval *fileName)
{
	bool handled;
	zv::Args argv{className, fileName};
	zv::Val twin = onTwin(handled, PT_LC("fromclass"), 2, argv);
	if (UNEXPECTED(handled)) return twin;
	if (UNEXPECTED(!entryArgument(className, false, "fromClass", 1, "className") || !entryArgument(fileName, true, "fromClass", 2, "fileName"))) return zv::Val();
	return InitializerExprContext::fromClass(Z_STR_P(className), fileName);
}

zv::Val pt_initializer_expr_context_from_function(zval *functionName, zval *fileName)
{
	bool handled;
	zv::Args argv{functionName, fileName};
	zv::Val twin = onTwin(handled, PT_LC("fromfunction"), 2, argv);
	if (UNEXPECTED(handled)) return twin;
	if (UNEXPECTED(!entryArgument(functionName, false, "fromFunction", 1, "functionName") || !entryArgument(fileName, true, "fromFunction", 2, "fileName"))) return zv::Val();
	return InitializerExprContext::fromFunction(Z_STR_P(functionName), fileName);
}

zv::Val pt_initializer_expr_context_from_class_method(zval *className, zval *traitName, zval *methodName, zval *fileName)
{
	bool handled;
	zv::Args argv{className, traitName, methodName, fileName};
	zv::Val twin = onTwin(handled, PT_LC("fromclassmethod"), 4, argv);
	if (UNEXPECTED(handled)) return twin;
	if (UNEXPECTED(!entryArgument(className, false, "fromClassMethod", 1, "className") || !entryArgument(traitName, true, "fromClassMethod", 2, "traitName") || !entryArgument(methodName, false, "fromClassMethod", 3, "methodName") || !entryArgument(fileName, true, "fromClassMethod", 4, "fileName"))) return zv::Val();
	return InitializerExprContext::fromClassMethod(Z_STR_P(className), traitName, Z_STR_P(methodName), fileName);
}

zv::Val pt_initializer_expr_context_from_reflection_parameter(zval *parameter)
{
	bool handled;
	zv::Val twin = onTwin(handled, PT_LC("fromreflectionparameter"), 1, parameter);
	if (UNEXPECTED(handled)) return twin;
	return InitializerExprContext::fromReflectionParameter(parameter);
}

zv::Val pt_initializer_expr_context_from_stub_parameter(zval *className, zval *stubFile, zval *function)
{
	bool handled;
	zv::Args argv{className, stubFile, function};
	zv::Val twin = onTwin(handled, PT_LC("fromstubparameter"), 3, argv);
	if (UNEXPECTED(handled)) return twin;
	if (UNEXPECTED(!entryArgument(className, true, "fromStubParameter", 1, "className") || !entryArgument(stubFile, false, "fromStubParameter", 2, "stubFile"))) return zv::Val();
	return InitializerExprContext::fromStubParameter(className, stubFile, function);
}

zv::Val pt_initializer_expr_context_from_global_constant(zval *constant)
{
	bool handled;
	zv::Val twin = onTwin(handled, PT_LC("fromglobalconstant"), 1, constant);
	if (UNEXPECTED(handled)) return twin;
	return InitializerExprContext::fromGlobalConstant(constant);
}

zv::Val pt_initializer_expr_context_create_empty()
{
	bool handled;
	zv::Val twin = onTwin(handled, PT_LC("createempty"), 0, NULL);
	if (UNEXPECTED(handled)) return twin;
	return InitializerExprContext::createEmpty();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_IEC_THIS InitializerExprContext(Z_OBJ_P(ZEND_THIS))

void pt_register_initializer_expr_context()
{
	pt_iec_closure = zend_string_init_interned(PT_LC("{closure}"), 1);

	reg::Class cls("PHPStan\\Reflection\\InitializerExprContext");
	ptdecl::InitializerExprContext::declareClass(cls);
	ptdecl::InitializerExprContext::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *file, *namespace_, *className, *traitName, *function, *method, *property;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_STR_OR_NULL(file)
			Z_PARAM_STR_OR_NULL(namespace_)
			Z_PARAM_STR_OR_NULL(className)
			Z_PARAM_STR_OR_NULL(traitName)
			Z_PARAM_STR_OR_NULL(function)
			Z_PARAM_STR_OR_NULL(method)
			Z_PARAM_STR_OR_NULL(property)
		ZEND_PARSE_PARAMETERS_END();
		zval args[7];
		zend_string *values[7] = {file, namespace_, className, traitName, function, method, property};
		for (int i = 0; i < 7; i++) {
			if (values[i] != NULL) {
				ZVAL_STR(&args[i], values[i]);
			} else {
				ZVAL_NULL(&args[i]);
			}
		}
		PT_IEC_THIS.construct(&args[0], &args[1], &args[2], &args[3], &args[4], &args[5], &args[6]);
	});

	cls.method(sigs::fromScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(scope, pt_class(PT_CLASS_SCOPE))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(InitializerExprContext::fromScope(scope));
	});

	cls.method(sigs::fromClassReflection, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			/* erased to object: a ClassReflection of either implementation (the
			 * differential tests hand the prefixed class the PHP twin's) */
			Z_PARAM_OBJECT(classReflection)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(InitializerExprContext::fromClassReflection(classReflection));
	});

	cls.method(sigs::fromClass, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *className, *fileName;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_STR(className)
			Z_PARAM_STR_OR_NULL(fileName)
		ZEND_PARSE_PARAMETERS_END();
		zval fileNameZv;
		if (fileName != NULL) {
			ZVAL_STR(&fileNameZv, fileName);
		} else {
			ZVAL_NULL(&fileNameZv);
		}
		PT_RETURN_VAL(InitializerExprContext::fromClass(className, &fileNameZv));
	});

	cls.method(sigs::fromFunction, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *functionName, *fileName;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_STR(functionName)
			Z_PARAM_STR_OR_NULL(fileName)
		ZEND_PARSE_PARAMETERS_END();
		zval fileNameZv;
		if (fileName != NULL) {
			ZVAL_STR(&fileNameZv, fileName);
		} else {
			ZVAL_NULL(&fileNameZv);
		}
		PT_RETURN_VAL(InitializerExprContext::fromFunction(functionName, &fileNameZv));
	});

	cls.method(sigs::fromClassMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *className, *traitName, *methodName, *fileName;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_STR(className)
			Z_PARAM_STR_OR_NULL(traitName)
			Z_PARAM_STR(methodName)
			Z_PARAM_STR_OR_NULL(fileName)
		ZEND_PARSE_PARAMETERS_END();
		zval traitNameZv, fileNameZv;
		if (traitName != NULL) {
			ZVAL_STR(&traitNameZv, traitName);
		} else {
			ZVAL_NULL(&traitNameZv);
		}
		if (fileName != NULL) {
			ZVAL_STR(&fileNameZv, fileName);
		} else {
			ZVAL_NULL(&fileNameZv);
		}
		PT_RETURN_VAL(InitializerExprContext::fromClassMethod(className, &traitNameZv, methodName, &fileNameZv));
	});

	cls.method(sigs::fromReflectionParameter, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parameter;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(parameter, pt_class(PT_CLASS_ADAPTER_REFLECTION_PARAMETER))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(InitializerExprContext::fromReflectionParameter(parameter));
	});

	cls.method(sigs::fromStubParameter, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *className, *stubFile;
		zval *function;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_STR_OR_NULL(className)
			Z_PARAM_STR(stubFile)
			Z_PARAM_OBJECT(function)
		ZEND_PARSE_PARAMETERS_END();
		bool accepted = false;
		for (int classIdx : {PT_CLASS_CLASS_METHOD_STMT, PT_CLASS_FUNCTION_STMT, PT_CLASS_PROPERTY_HOOK}) {
			zend_class_entry *ce = pt_class_loaded(classIdx);
			if (UNEXPECTED(EG(exception))) RETURN_THROWS();
			if (ce != NULL && instanceof_function(Z_OBJCE_P(function), ce)) {
				accepted = true;
				break;
			}
		}
		if (UNEXPECTED(!accepted)) {
			zend_argument_type_error(3, "must be of type PhpParser\\Node\\Stmt\\ClassMethod|PhpParser\\Node\\Stmt\\Function_|PhpParser\\Node\\PropertyHook, %s given", zend_zval_value_name(function));
			RETURN_THROWS();
		}
		zval classNameZv, stubFileZv;
		if (className != NULL) {
			ZVAL_STR(&classNameZv, className);
		} else {
			ZVAL_NULL(&classNameZv);
		}
		ZVAL_STR(&stubFileZv, stubFile);
		PT_RETURN_VAL(InitializerExprContext::fromStubParameter(&classNameZv, &stubFileZv, function));
	});

	cls.method(sigs::fromGlobalConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constant;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(constant, pt_class(PT_CLASS_BETTER_REFLECTION_CONSTANT))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(InitializerExprContext::fromGlobalConstant(constant));
	});

	cls.method<&InitializerExprContext::createEmpty>(sigs::createEmpty);
	cls.method<&InitializerExprContext::getFile>(sigs::getFile);
	cls.method<&InitializerExprContext::getClassName>(sigs::getClassName);
	cls.method<&InitializerExprContext::getNamespace>(sigs::getNamespace);
	cls.method<&InitializerExprContext::getTraitName>(sigs::getTraitName);
	cls.method<&InitializerExprContext::getFunction>(sigs::getFunction);
	cls.method<&InitializerExprContext::getMethod>(sigs::getMethod);
	cls.method<&InitializerExprContext::getProperty>(sigs::getProperty);

	cls.shadow(&pt_ce_initializer_expr_context);
}

/* }}} */
