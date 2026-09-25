/*
 * PHPStanTurbo\ImplicitToStringCallHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processImplicitToStringCall() is exported
 * as pt_implicit_to_string_call_helper_process_implicit_to_string_call() for
 * the handlers that stringify an operand (concatenation, echo, print,
 * interpolation, string casts, `.=`). The results' `static fn () => new
 * MixedType()` type callbacks are native closures capturing nothing.
 *
 * MutatingScope, ExpressionResult, ExpressionContext, ImpurePoint, the
 * method reflections, ClassReflection, ParametersAcceptorSelector,
 * MethodCallReturnTypeHelper, MethodThrowPointHelper, SpecifiedTypes and the
 * Type kernel are called through their direct entries; PhpVersion's
 * throwsOnStringCast() reads the final class's versionId slot (the method
 * otherwise), and the combined acceptor's getNativeReturnType() goes through
 * the cached site of CallHandlerSupport.h.
 */

#include "support.h"
#include "generated/ImplicitToStringCallHelper.h"

namespace slots = ptdecl::ImplicitToStringCallHelper::slot;
namespace sigs = ptdecl::ImplicitToStringCallHelper::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_implicit_to_string_call_helper = nullptr;

namespace {

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_itsch_to_string = nullptr;
zend_string *pt_itsch_method_call = nullptr;
zend_string *pt_itsch_synthetic_site_attribute = nullptr;


/* $phpVersion->throwsOnStringCast(): `$this->versionId >= 70400` of exactly
 * the final PhpVersion, the method otherwise; false = pending exception */
[[nodiscard]] bool throwsOnStringCast(zval *phpVersion, bool &out)
{
	return pt_php_version_answer(phpVersion, PT_PHP_VERSION_THROWS_ON_STRING_CAST, out);
}

/* sprintf('call to method %s::%s()', $declaringClassDisplayName, $methodName) */
zend_string *callToMethodDescription(zval *displayName, zval *methodName)
{
	zend_string *displayNameString = zval_get_string(displayName);
	zend_string *methodNameString = zval_get_string(methodName);
	smart_str description = {NULL, 0};
	smart_str_appends(&description, "call to method ");
	smart_str_append(&description, displayNameString);
	smart_str_appends(&description, "::");
	smart_str_append(&description, methodNameString);
	smart_str_appends(&description, "()");
	zend_string_release(displayNameString);
	zend_string_release(methodNameString);
	return smart_str_extract(&description);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
 * UNDEF = pending exception. */
class ImplicitToStringCallHelper
{
public:
	explicit ImplicitToStringCallHelper(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *phpVersion, zval *methodThrowPointHelper, zval *methodCallReturnTypeHelper, zval *expressionResultFactory) const
	{
		pt_write_slot(self, slots::phpVersion, phpVersion);
		pt_write_slot(self, slots::methodThrowPointHelper, methodThrowPointHelper);
		pt_write_slot(self, slots::methodCallReturnTypeHelper, methodCallReturnTypeHelper);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
	}

	/* Mirrors processImplicitToStringCall(). */
	zv::Val processImplicitToStringCall(zval *expr, zval *scope, zval *exprResult) const
	{
		bool nativeTypesPromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
		zv::Val exprType = pt_expression_result_get_type_on_scope(exprResult, scope, nativeTypesPromoted);
		if (UNEXPECTED(exprType.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(exprType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isObject() on %s", zend_zval_value_name(exprType.raw()));
			return zv::Val();
		}

		zv::Val toStringMethod = zv::Val::null();
		zend_long isObject = pt_type_call_trinary(Z_OBJ_P(exprType.raw()), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return zv::Val();
		if (isObject != PT_TRI_NO) {
			toStringMethod = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), exprType.raw(), pt_itsch_to_string);
			if (UNEXPECTED(toStringMethod.isUndef())) return zv::Val();
		}
		if (toStringMethod.isNull()) {
			return createResult(expr, scope, NULL, NULL);
		}

		zv::Arr throwPoints = zv::Arr::empty();
		zv::Arr impurePoints = zv::Arr::empty();
		zval *method = toStringMethod.raw();

		zend_long hasSideEffects = pt_extended_method_reflection_trinary(method, PT_MR_HAS_SIDE_EFFECTS);
		if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
		if (hasSideEffects != PT_TRI_NO) {
			zv::Val declaringClass = pt_extended_method_reflection_call(method, PT_MR_GET_DECLARING_CLASS);
			if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(declaringClass.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getDisplayName() on %s", zend_zval_value_name(declaringClass.raw()));
				return zv::Val();
			}
			zv::Val displayName = pt_class_reflection_get_display_name(Z_OBJ_P(declaringClass.raw()), true);
			if (UNEXPECTED(displayName.isUndef())) return zv::Val();
			zv::Val methodName = pt_extended_method_reflection_call(method, PT_MR_GET_NAME);
			if (UNEXPECTED(methodName.isUndef())) return zv::Val();
			zend_string *description = callToMethodDescription(displayName.raw(), methodName.raw());
			if (UNEXPECTED(EG(exception))) {
				zend_string_release(description);
				return zv::Val();
			}
			zend_long isPure = pt_extended_method_reflection_trinary(method, PT_MR_IS_PURE);
			if (UNEXPECTED(isPure < 0)) {
				zend_string_release(description);
				return zv::Val();
			}
			zv::Val impurePoint = pt_impure_point_new(scope, expr, pt_itsch_method_call, description, isPure == PT_TRI_NO);
			zend_string_release(description);
			if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
			impurePoints.push(std::move(impurePoint));
		}

		bool throws;
		if (UNEXPECTED(!throwsOnStringCast(OBJ_PROP_NUM(self, slots::phpVersion), throws))) return zv::Val();
		if (throws) {
			// the __toString() call's return type resolves directly (the receiver
			// type is already in hand); the fabricated node is only the payload
			// dynamic extensions receive - nothing walks it
			zv::Val toStringCall = newToStringCall(expr);
			if (UNEXPECTED(toStringCall.isUndef())) return zv::Val();
			zv::Val toStringReturnType;
			if (nativeTypesPromoted) {
				zv::Val variants = pt_extended_method_reflection_call(method, PT_MR_GET_VARIANTS);
				if (UNEXPECTED(variants.isUndef())) return zv::Val();
				zv::Val acceptor = ptcall::combineAcceptors(variants.raw());
				if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
				toStringReturnType = ptcall::acceptorNativeReturnType(acceptor.raw());
			} else {
				zv::Val methodName = zv::Val::string(pt_itsch_to_string);
				toStringReturnType = pt_method_call_return_type_helper_method_call_return_type(OBJ_PROP_NUM(self, slots::methodCallReturnTypeHelper), scope, exprType.raw(), methodName.raw(), toStringCall.raw(), NULL, NULL);
				if (UNEXPECTED(toStringReturnType.isUndef())) return zv::Val();
				if (toStringReturnType.isNull()) {
					zval errorType;
					if (UNEXPECTED(!pt_error_type_new(&errorType))) return zv::Val();
					toStringReturnType = zv::Val::adopt(errorType);
				}
			}
			if (UNEXPECTED(toStringReturnType.isUndef())) return zv::Val();
			zv::Val onlyVariant = pt_extended_method_reflection_call(method, PT_MR_GET_ONLY_VARIANT);
			if (UNEXPECTED(onlyVariant.isUndef())) return zv::Val();
			zv::Val context = pt_expression_context_create_deep();
			if (UNEXPECTED(context.isUndef())) return zv::Val();
			zv::Val throwPoint = pt_method_throw_point_helper_get_throw_point(OBJ_PROP_NUM(self, slots::methodThrowPointHelper), method, onlyVariant.raw(), toStringCall.raw(), scope, context.raw(), toStringReturnType.raw());
			if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
			if (!throwPoint.isNull()) {
				throwPoints.push(std::move(throwPoint));
			}
		}

		return createResult(expr, scope, throwPoints.raw(), impurePoints.raw());
	}

private:
	zend_object *self;

	/* $this->expressionResultFactory->create($scope, beforeScope: $scope, expr:
	 * $expr, hasYield: false, isAlwaysTerminating: false, throwPoints: ...,
	 * impurePoints: ..., typeCallback: static fn () => new MixedType(),
	 * specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback()) */
	zv::Val createResult(zval *expr, zval *scope, zval *throwPoints, zval *impurePoints) const
	{
		zv::Val typeCallback = pt_native_closure(&mixedTypeCallbackBody);
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args args(scope, scope, expr, false, false, throwPoints, impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* new Expr\MethodCall($expr, new Identifier('__toString'), attributes:
	 * [TemplateArgumentFrame::SYNTHETIC_SITE_ATTRIBUTE => true]) */
	static zv::Val newToStringCall(zval *expr)
	{
		zval nameZv;
		ZVAL_STR(&nameZv, pt_itsch_to_string);
		zv::Val identifier = pt_name_node_new(PT_CLASS_IDENTIFIER, &nameZv);
		if (UNEXPECTED(identifier.isUndef())) return zv::Val();
		zv::Arr attributes = zv::Arr::create(1);
		attributes.set(pt_itsch_synthetic_site_attribute, zv::Val::boolean(true));
		zv::Arr args = zv::Arr::empty();
		zv::Args argv{expr, identifier.raw(), args.raw(), attributes.raw()};
		return pt_type_new(PT_CLASS_METHOD_CALL, 4, argv);
	}

	/* static fn () => new MixedType() */
	static void mixedTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argc;
		(void) argv;
		zv::Val type = pt_type_new_mixed_type();
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ImplicitToStringCallHelper;

zv::Val pt_implicit_to_string_call_helper_process_implicit_to_string_call(zval *helper, zval *expr, zval *scope, zval *exprResult)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_implicit_to_string_call_helper)) return ImplicitToStringCallHelper(Z_OBJ_P(helper)).processImplicitToStringCall(expr, scope, exprResult);
	zv::Args argv{expr, scope, exprResult};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("processimplicittostringcall"), 3, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_implicit_to_string_call_helper)
{
	pt_itsch_to_string = zend_string_init_interned(PT_LC("__toString"), 1);
	pt_itsch_method_call = zend_string_init_interned(PT_LC("methodCall"), 1);
	pt_itsch_synthetic_site_attribute = zend_string_init_interned(PT_LC("templateArgumentSyntheticSite"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\ImplicitToStringCallHelper");
	ptdecl::ImplicitToStringCallHelper::declareClass(cls);
	ptdecl::ImplicitToStringCallHelper::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion, *methodThrowPointHelper, *methodCallReturnTypeHelper, *expressionResultFactory;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, phpVersion, methodThrowPointHelper, methodCallReturnTypeHelper, expressionResultFactory)) RETURN_THROWS();
		ImplicitToStringCallHelper(Z_OBJ_P(ZEND_THIS)).construct(phpVersion, methodThrowPointHelper, methodCallReturnTypeHelper, expressionResultFactory);
	});

	cls.method(sigs::processImplicitToStringCall, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *scope, *exprResult;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, expr, scope, exprResult)) RETURN_THROWS();
		PT_RETURN_VAL(ImplicitToStringCallHelper(Z_OBJ_P(ZEND_THIS)).processImplicitToStringCall(expr, scope, exprResult));
	});

	cls.shadow(&pt_ce_implicit_to_string_call_helper);
}

/* }}} */
