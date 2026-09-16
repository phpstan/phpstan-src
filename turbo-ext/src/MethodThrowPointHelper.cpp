/*
 * PHPStanTurbo\MethodThrowPointHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's exact
 * arginfo (the two #[AutowiredExtensions] collections and the
 * #[AutowiredParameter] bool), the state lives in the twin's property slots
 * (generated declarations).
 *
 * The dynamic throw-type extensions stay PHP and are called through the
 * engine; the extension lists come out of the LazyExtensionsCollection memo
 * slot (ReflectionAccess.cpp), the Type queries go through the native ops,
 * ExpressionContext and InternalThrowPoint through their direct entries.
 * The method reflections are asked through their direct entry; the
 * analyser classes still PHP — ParametersAcceptorSelector — are called by
 * name.
 */

#include "support.h"
#include "generated/MethodThrowPointHelper.h"

namespace slots = ptdecl::MethodThrowPointHelper::slot;
namespace sigs = ptdecl::MethodThrowPointHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "ParserVisitors.h"

static zend_class_entry *pt_ce_method_throw_point_helper;

namespace {

using phpstanturbo::visitors::NodeProp;

/* persistent interned literals, created at registration */
zend_string *pt_mtph_throwable = NULL;
zend_string *pt_mtph_invoke = NULL;
zend_string *pt_mtph_invoke_args = NULL;
zend_string *pt_mtph_reflection_method = NULL;
zend_string *pt_mtph_reflection_function = NULL;

NodeProp pt_mtph_method_call_name = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "name");

/* InternalThrowPoint::createExplicit($scope, $type, $node, $canContainAnyThrowable) */
zv::Val createExplicit(zval *scope, zval *type, zval *node, bool canContainAnyThrowable)
{
	return pt_internal_throw_point_create_explicit(scope, type, node, canContainAnyThrowable, false);
}

/* InternalThrowPoint::createImplicit($scope, $node) */
zv::Val createImplicit(zval *scope, zval *node)
{
	return pt_internal_throw_point_create_implicit(scope, node);
}

/* $value->method(...$args) on a value that must be an object — the Error
 * PHP raises for a member call on anything else */
zv::Val callOn(zval *value, const char *lcname, size_t len, const char *name, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(value));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(value), lcname, len, argc, argv);
}

/* $methodReflection->method() of a value that must be an object — through
 * the method reflections' direct entry (ResolvedMethodReflection.cpp) */
zv::Val reflectionCall(zval *methodReflection, pt_method_reflection_member member, const char *name)
{
	if (UNEXPECTED(Z_TYPE_P(methodReflection) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(methodReflection));
		return zv::Val();
	}
	return pt_extended_method_reflection_call(methodReflection, member);
}

/* in_array($value, [$a, $b], true) for a string pair */
bool isOneOf(zval *value, zend_string *a, zend_string *b)
{
	return Z_TYPE_P(value) == IS_STRING && (zend_string_equals(Z_STR_P(value), a) || zend_string_equals(Z_STR_P(value), b));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper. */
class MethodThrowPointHelper
{
public:
	explicit MethodThrowPointHelper(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties */
	static void construct(zend_object *object, zval *dynamicMethodThrowTypeExtensions, zval *dynamicStaticMethodThrowTypeExtensions, bool implicitThrows)
	{
		writeSlot(object, slots::dynamicMethodThrowTypeExtensions, zv::Val::copyOf(zv::Ref(dynamicMethodThrowTypeExtensions)));
		writeSlot(object, slots::dynamicStaticMethodThrowTypeExtensions, zv::Val::copyOf(zv::Ref(dynamicStaticMethodThrowTypeExtensions)));
		writeSlot(object, slots::implicitThrows, zv::Val::boolean(implicitThrows));
	}

	/* Mirrors getThrowPoint(); the throw point or PHP null, UNDEF = pending
	 * exception */
	zv::Val getThrowPoint(zval *methodReflection, zval *parametersAcceptor, zval *normalizedMethodCall, zval *scope, zval *context, zval *methodCallReturnType) const
	{
		zend_class_entry *methodCallCe = pt_class(PT_CLASS_METHOD_CALL);
		if (UNEXPECTED(methodCallCe == NULL)) return zv::Val();
		bool isMethodCall = instanceof_function(Z_OBJCE_P(normalizedMethodCall), methodCallCe);

		zval *collection = slot(isMethodCall ? slots::dynamicMethodThrowTypeExtensions : slots::dynamicStaticMethodThrowTypeExtensions);
		if (UNEXPECTED(Z_TYPE_P(collection) != IS_OBJECT)) return uninitialized(isMethodCall ? "dynamicMethodThrowTypeExtensions" : "dynamicStaticMethodThrowTypeExtensions");
		zv::Val extensions = pt_extensions_collection_get_all(Z_OBJ_P(collection));
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		if (EXPECTED(Z_TYPE_P(extensions.raw()) == IS_ARRAY)) {
			for (zv::ArrayEntry entry : zv::ArrRef(extensions.raw())) {
				zval *extension = entry.value().deref().raw();
				zv::Val supported = isMethodCall
					? callOn(extension, PT_LC("ismethodsupported"), "isMethodSupported", 1, methodReflection)
					: callOn(extension, PT_LC("isstaticmethodsupported"), "isStaticMethodSupported", 1, methodReflection);
				if (UNEXPECTED(supported.isUndef())) return zv::Val();
				if (!zend_is_true(supported.raw())) continue;

				zv::Args args{methodReflection, normalizedMethodCall, scope};
				zv::Val throwType = isMethodCall
					? pt_type_call(Z_OBJ_P(extension), PT_LC("getthrowtypefrommethodcall"), 3, args)
					: pt_type_call(Z_OBJ_P(extension), PT_LC("getthrowtypefromstaticmethodcall"), 3, args);
				if (UNEXPECTED(throwType.isUndef())) return zv::Val();
				if (Z_TYPE_P(throwType.raw()) == IS_NULL) return zv::Val::null();
				if (UNEXPECTED(!throwType.ref().isObject())) {
					zend_throw_error(NULL, "Call to a member function isVoid() on %s", zend_zval_value_name(throwType.raw()));
					return zv::Val();
				}
				zend_long throwTypeIsVoid = pt_type_op_trinary(Z_OBJ_P(throwType.raw()), PT_OP_IS_VOID, 0, NULL);
				if (UNEXPECTED(throwTypeIsVoid < 0)) return zv::Val();
				if (throwTypeIsVoid == PT_TRI_YES) return zv::Val::null();

				return createExplicit(scope, throwType.raw(), normalizedMethodCall, false);
			}
		}

		if (isMethodCall) {
			zv::Val name = reflectionCall(methodReflection, PT_MR_GET_NAME, "getName");
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			if (isOneOf(name.raw(), pt_mtph_invoke, pt_mtph_invoke_args)) {
				zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_DECLARING_CLASS);
				if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(declaringClass.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(declaringClass.raw()));
					return zv::Val();
				}
				zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
				if (UNEXPECTED(className.isUndef())) return zv::Val();
				if (isOneOf(className.raw(), pt_mtph_reflection_method, pt_mtph_reflection_function)) return createImplicit(scope, normalizedMethodCall);
			}
		}

		zv::Val throwType = reflectionCall(methodReflection, PT_MR_GET_THROW_TYPE, "getThrowType");
		if (UNEXPECTED(throwType.isUndef())) return zv::Val();
		if (Z_TYPE_P(throwType.raw()) != IS_NULL) {
			zv::Val callArgs = pt_type_call(Z_OBJ_P(normalizedMethodCall), PT_LC("getargs"), 0, NULL);
			if (UNEXPECTED(callArgs.isUndef())) return zv::Val();
			throwType = pt_conditional_type_resolver_resolve_for_call(throwType.raw(), parametersAcceptor, callArgs.raw(), scope);
			if (UNEXPECTED(throwType.isUndef())) return zv::Val();
		}
		if (Z_TYPE_P(throwType.raw()) == IS_NULL && instanceof_function(Z_OBJCE_P(methodCallReturnType), pt_ce_never_type)) {
			bool isExplicit;
			if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(methodCallReturnType), isExplicit))) return zv::Val();
			if (isExplicit) {
				zval throwable;
				if (UNEXPECTED(!pt_object_type_new(&throwable, pt_mtph_throwable))) return zv::Val();
				throwType = zv::Val::adopt(throwable);
			}
		}

		if (Z_TYPE_P(throwType.raw()) != IS_NULL) {
			if (UNEXPECTED(Z_TYPE_P(throwType.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isVoid() on %s", zend_zval_value_name(throwType.raw()));
				return zv::Val();
			}
			zend_long isVoid = pt_type_op_trinary(Z_OBJ_P(throwType.raw()), PT_OP_IS_VOID, 0, NULL);
			if (UNEXPECTED(isVoid < 0)) return zv::Val();
			if (isVoid != PT_TRI_YES) return createExplicit(scope, throwType.raw(), normalizedMethodCall, true);
		} else {
			zval *implicitThrows = slot(slots::implicitThrows);
			if (UNEXPECTED(Z_TYPE_P(implicitThrows) != IS_TRUE && Z_TYPE_P(implicitThrows) != IS_FALSE)) return uninitialized("implicitThrows");
			if (Z_TYPE_P(implicitThrows) == IS_TRUE) {
				bool inThrow;
				if (UNEXPECTED(!pt_expression_context_is_in_throw(context, inThrow))) return zv::Val();
				if (!inThrow) return createImplicit(scope, normalizedMethodCall);
				zval throwable;
				if (UNEXPECTED(!pt_object_type_new(&throwable, pt_mtph_throwable))) return zv::Val();
				zv::Val throwableType = zv::Val::adopt(throwable);
				zv::Val isSuperType = pt_type_op(Z_OBJ_P(throwableType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, methodCallReturnType);
				if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
				zend_long verdict = pt_type_result_trinary(isSuperType.raw());
				if (UNEXPECTED(verdict < 0)) return zv::Val();
				if (verdict != PT_TRI_YES) return createImplicit(scope, normalizedMethodCall);
			}
		}

		return zv::Val::null();
	}

	/* Mirrors getThrowPointsForCallOnType(); UNDEF = pending exception */
	zv::Val getThrowPointsForCallOnType(zval *scope, zval *context, zval *calledOnType, zval *methodCall) const
	{
		zend_class_entry *identifierCe = pt_class(PT_CLASS_IDENTIFIER);
		if (UNEXPECTED(identifierCe == NULL)) return zv::Val();
		zval *name = pt_mtph_method_call_name.of(Z_OBJ_P(methodCall));
		if (name == NULL || Z_TYPE_P(name) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(name), identifierCe)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Val methodName = pt_type_call(Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
		if (UNEXPECTED(methodName.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(methodName.raw()) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::getMethodReflection(): Argument #2 ($methodName) must be of type string, %s given", zend_zval_value_name(methodName.raw()));
			return zv::Val();
		}
		zv::Val methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), calledOnType, Z_STR_P(methodName.raw()));
		if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
		if (Z_TYPE_P(methodReflection.raw()) == IS_NULL) {
			zv::Val throwPoint = createImplicit(scope, methodCall);
			if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
			zv::Arr list = zv::Arr::create(1);
			list.push(std::move(throwPoint));
			return zv::Val(std::move(list));
		}

		zv::Val args = pt_type_call(Z_OBJ_P(methodCall), PT_LC("getargs"), 0, NULL);
		if (UNEXPECTED(args.isUndef())) return zv::Val();
		zv::Val variants = reflectionCall(methodReflection.raw(), PT_MR_GET_VARIANTS, "getVariants");
		if (UNEXPECTED(variants.isUndef())) return zv::Val();
		zv::Val namedArgumentsVariants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
		if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
		zv::Args combineArgs{args.raw(), variants.raw(), namedArgumentsVariants.raw()};
		zv::Val parametersAcceptor = pt_type_call_static(PT_CLASS_PARAMETERS_ACCEPTOR_SELECTOR, PT_LC("combinevariantsfornormalization"), 3, combineArgs);
		if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();
		zv::Val returnType = callOn(parametersAcceptor.raw(), PT_LC("getreturntype"), "getReturnType", 0, NULL);
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(returnType.raw()) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\MethodThrowPointHelper::getThrowPoint(): Argument #6 ($methodCallReturnType) must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(returnType.raw()));
			return zv::Val();
		}

		zv::Val throwPoint = getThrowPoint(methodReflection.raw(), parametersAcceptor.raw(), methodCall, scope, context, returnType.raw());
		if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
		if (Z_TYPE_P(throwPoint.raw()) == IS_NULL) return zv::Val(zv::Arr::empty());

		zv::Arr list = zv::Arr::create(1);
		list.push(std::move(throwPoint));
		return zv::Val(std::move(list));
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	/* the engine's Error for reading a never-written typed property */
	zv::Val uninitialized(const char *property) const
	{
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), property);
		return zv::Val();
	}

	static void writeSlot(zend_object *object, uint32_t index, zv::Val value)
	{
		zv::ObjRef(object).propAtWrite(index, std::move(value));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(object, index)) = 0; /* no longer IS_PROP_UNINIT */
	}
};

} // namespace phpstanturbo

using phpstanturbo::MethodThrowPointHelper;

/* {{{ direct entries (support.h) */

zv::Val pt_method_throw_point_helper_get_throw_point(zval *helper, zval *methodReflection, zval *parametersAcceptor, zval *normalizedMethodCall, zval *scope, zval *context, zval *methodCallReturnType)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_method_throw_point_helper)) return MethodThrowPointHelper(Z_OBJ_P(helper)).getThrowPoint(methodReflection, parametersAcceptor, normalizedMethodCall, scope, context, methodCallReturnType);
	zv::Args argv{methodReflection, parametersAcceptor, normalizedMethodCall, scope, context, methodCallReturnType};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("getthrowpoint"), 6, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_method_throw_point_helper()
{
	pt_mtph_throwable = zend_string_init_interned(PT_LC("Throwable"), 1);
	pt_mtph_invoke = zend_string_init_interned(PT_LC("invoke"), 1);
	pt_mtph_invoke_args = zend_string_init_interned(PT_LC("invokeArgs"), 1);
	pt_mtph_reflection_method = zend_string_init_interned(PT_LC("ReflectionMethod"), 1);
	pt_mtph_reflection_function = zend_string_init_interned(PT_LC("ReflectionFunction"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\MethodThrowPointHelper");
	ptdecl::MethodThrowPointHelper::declareClass(cls);
	ptdecl::MethodThrowPointHelper::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly (README rule 6) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *dynamicMethodThrowTypeExtensions, *dynamicStaticMethodThrowTypeExtensions;
		bool implicitThrows;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Bool>(execute_data, dynamicMethodThrowTypeExtensions, dynamicStaticMethodThrowTypeExtensions, implicitThrows)) RETURN_THROWS();
		MethodThrowPointHelper::construct(Z_OBJ_P(ZEND_THIS), dynamicMethodThrowTypeExtensions, dynamicStaticMethodThrowTypeExtensions, implicitThrows);
	});

	cls.method(sigs::getThrowPoint, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *methodReflection, *parametersAcceptor, *normalizedMethodCall, *scope, *context, *methodCallReturnType;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, methodReflection, parametersAcceptor, normalizedMethodCall, scope, context, methodCallReturnType)) RETURN_THROWS();
		PT_RETURN_VAL(MethodThrowPointHelper(Z_OBJ_P(ZEND_THIS)).getThrowPoint(methodReflection, parametersAcceptor, normalizedMethodCall, scope, context, methodCallReturnType));
	});

	cls.method<&MethodThrowPointHelper::getThrowPointsForCallOnType, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(sigs::getThrowPointsForCallOnType);

	cls.shadow(&pt_ce_method_throw_point_helper);
}

/* }}} */
