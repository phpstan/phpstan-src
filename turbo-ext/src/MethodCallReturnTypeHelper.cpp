/*
 * PHPStanTurbo\MethodCallReturnTypeHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's exact
 * arginfo, the state lives in the twin's property slots (generated
 * declarations).
 *
 * The scope and Type work — filterTypeWithMethod(), getMethod(),
 * getObjectClassNames(), hasMethod(), the TypeCombinator — is native, as is
 * TemplateArgumentFrame::returnTypeOfCall(). The dynamic return type
 * extensions stay PHP and are called through the engine, as are the
 * analyser classes still PHP (DynamicReturnTypeExtensionRegistry,
 * ArgumentsNormalizer, ParametersAcceptorSelector), each from one small
 * local helper — the place to switch to a direct entry once its class is
 * native. The method reflections and DynamicReturnTypeStoragePrimer are
 * reached through their direct entries (the primer's push/pop without the
 * pop closure).
 *
 * The twin's try/finally around the extension dispatch is pt_finally().
 */

#include "support.h"
#include "generated/MethodCallReturnTypeHelper.h"

namespace slots = ptdecl::MethodCallReturnTypeHelper::slot;
namespace sigs = ptdecl::MethodCallReturnTypeHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"

static zend_class_entry *pt_ce_method_call_return_type_helper;

namespace {

/* {{{ the analyser classes still PHP: one local helper per call */

/* TemplateArgumentFrame::returnTypeOfCall($acceptor, $scope, $site) */
zv::Val returnTypeOfCall(zval *acceptor, zval *scope, zval *site)
{
	return pt_template_argument_frame_return_type_of_call(acceptor, scope, site);
}

/* ArgumentsNormalizer::reorderMethodArguments($acceptor, $call) /
 * reorderStaticCallArguments($acceptor, $call) */
zv::Val reorderArguments(bool isMethodCall, zval *acceptor, zval *call)
{
	return isMethodCall
		? pt_arguments_normalizer_reorder_method_arguments(acceptor, call)
		: pt_arguments_normalizer_reorder_static_call_arguments(acceptor, call);
}

/* ParametersAcceptorSelector::selectFromArgs($scope, $call->getArgs(),
 * $methodReflection->getVariants(), $methodReflection->getNamedArgumentsVariants()) */
zv::Val selectFromArgs(zval *scope, zval *call, zval *methodReflection)
{
	zv::Val argsHold;
	zval *args = pt_call_like_args(Z_OBJ_P(call), argsHold);
	if (UNEXPECTED(args == NULL)) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(methodReflection) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getVariants() on %s", zend_zval_value_name(methodReflection));
		return zv::Val();
	}
	zv::Val variants = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_VARIANTS);
	if (UNEXPECTED(variants.isUndef())) return zv::Val();
	zv::Val namedArgumentsVariants = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
	if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
	zv::Args selectArgs{scope, args, variants.raw(), namedArgumentsVariants.raw()};
	return pt_type_call_static(PT_CLASS_PARAMETERS_ACCEPTOR_SELECTOR, PT_LC("selectfromargs"), 4, selectArgs);
}

/* $type->getMethod($methodName, $scope) */
zv::Val getMethod(zval *type, zval *methodName, zval *scope)
{
	zv::Args args{methodName, scope};
	return pt_type_call(Z_OBJ_P(type), PT_LC("getmethod"), 2, args);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper. */
class MethodCallReturnTypeHelper
{
public:
	explicit MethodCallReturnTypeHelper(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties */
	static void construct(zend_object *object, zval *dynamicReturnTypeExtensionRegistry, zval *storagePrimer)
	{
		writeSlot(object, slots::dynamicReturnTypeExtensionRegistry, zv::Val::copyOf(zv::Ref(dynamicReturnTypeExtensionRegistry)));
		writeSlot(object, slots::storagePrimer, zv::Val::copyOf(zv::Ref(storagePrimer)));
	}

	/* Mirrors methodCallReturnType(); $preResolvedAcceptor / $argsResult NULL
	 * for null. The type or PHP null, UNDEF = pending exception */
	zv::Val methodCallReturnType(zval *scope, zval *typeWithMethodArg, zend_string *methodName, zval *methodCall, zval *preResolvedAcceptor, zval *argsResult) const
	{
		zv::Val typeWithMethod = pt_mutating_scope_filter_type_with_method(Z_OBJ_P(scope), typeWithMethodArg, methodName);
		if (UNEXPECTED(typeWithMethod.isUndef())) return zv::Val();
		if (Z_TYPE_P(typeWithMethod.raw()) == IS_NULL) return zv::Val::null();

		zval methodNameZv;
		ZVAL_STR(&methodNameZv, methodName);
		zv::Val methodReflection = getMethod(typeWithMethod.raw(), &methodNameZv, scope);
		if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();

		zv::Val selectedAcceptor;
		zval *parametersAcceptor = preResolvedAcceptor;
		if (parametersAcceptor == NULL) {
			selectedAcceptor = selectFromArgs(scope, methodCall, methodReflection.raw());
			if (UNEXPECTED(selectedAcceptor.isUndef())) return zv::Val();
			parametersAcceptor = selectedAcceptor.raw();
		}

		zend_class_entry *methodCallCe = pt_class(PT_CLASS_METHOD_CALL);
		if (UNEXPECTED(methodCallCe == NULL)) return zv::Val();
		bool isMethodCall = instanceof_function(Z_OBJCE_P(methodCall), methodCallCe);
		zv::Val normalizedMethodCall = reorderArguments(isMethodCall, parametersAcceptor, methodCall);
		if (UNEXPECTED(normalizedMethodCall.isUndef())) return zv::Val();
		if (Z_TYPE_P(normalizedMethodCall.raw()) == IS_NULL) return returnTypeOfCall(parametersAcceptor, scope, methodCall);

		// re-expose the already-processed arguments so an extension's
		// Scope::getType($arg->value) reads the stored result instead of re-walking
		// the argument on demand (the call's argument storage frame is no longer
		// current when the return type is asked lazily)
		zval *storagePrimer = slot(slots::storagePrimer);
		if (UNEXPECTED(Z_TYPE_P(storagePrimer) != IS_OBJECT)) return uninitialized("storagePrimer");
		pt_primed_storage primed;
		if (UNEXPECTED(!pt_dynamic_return_type_storage_primer_push(storagePrimer, scope, argsResult, primed))) return zv::Val();

		bool returned = false;
		zv::Val result = dispatchExtensions(scope, typeWithMethod.raw(), &methodNameZv, methodCall, methodReflection.raw(), normalizedMethodCall.raw(), isMethodCall, returned);
		pt_finally([&]() { (void) pt_dynamic_return_type_storage_primer_pop(primed); });
		if (UNEXPECTED(result.isUndef() || EG(exception) != NULL)) return zv::Val();
		if (returned) return result;

		return returnTypeOfCall(parametersAcceptor, scope, methodCall);
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

	/* the twin's try block: the union of the extension-resolved types
	 * ($returned set) or PHP null with $returned clear when no extension
	 * resolved one; UNDEF = pending exception */
	zv::Val dispatchExtensions(zval *scope, zval *typeWithMethod, zval *methodName, zval *methodCall, zval *methodReflection, zval *normalizedMethodCall, bool isMethodCall, bool &returned) const
	{
		zv::Arr resolvedTypes = zv::Arr::empty();
		zv::Val allClassNames = pt_type_op(Z_OBJ_P(typeWithMethod), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
		if (UNEXPECTED(allClassNames.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(allClassNames.raw()) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: getObjectClassNames() must return array, %s returned", zend_zval_value_name(allClassNames.raw()));
			return zv::Val();
		}
		zv::Arr handledClassNames = zv::Arr::empty();

		zval *registry = slot(slots::dynamicReturnTypeExtensionRegistry);
		if (UNEXPECTED(Z_TYPE_P(registry) != IS_OBJECT)) return uninitialized("dynamicReturnTypeExtensionRegistry");
		for (zv::ArrayEntry classEntry : zv::ArrRef(allClassNames.raw())) {
			zval *className = classEntry.value().deref().raw();
			zv::Val extensions = isMethodCall
				? pt_type_call(Z_OBJ_P(registry), PT_LC("getdynamicmethodreturntypeextensionsforclass"), 1, className)
				: pt_type_call(Z_OBJ_P(registry), PT_LC("getdynamicstaticmethodreturntypeextensionsforclass"), 1, className);
			if (UNEXPECTED(extensions.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(extensions.raw()) != IS_ARRAY)) continue;
			for (zv::ArrayEntry extensionEntry : zv::ArrRef(extensions.raw())) {
				zval *extension = extensionEntry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(extension) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function %s() on %s", isMethodCall ? "isMethodSupported" : "isStaticMethodSupported", zend_zval_value_name(extension));
					return zv::Val();
				}
				zv::Val supported = isMethodCall
					? pt_type_call(Z_OBJ_P(extension), PT_LC("ismethodsupported"), 1, methodReflection)
					: pt_type_call(Z_OBJ_P(extension), PT_LC("isstaticmethodsupported"), 1, methodReflection);
				if (UNEXPECTED(supported.isUndef())) return zv::Val();
				if (!zend_is_true(supported.raw())) continue;

				zv::Args args{methodReflection, normalizedMethodCall, scope};
				zv::Val resolvedType = isMethodCall
					? pt_type_call(Z_OBJ_P(extension), PT_LC("gettypefrommethodcall"), 3, args)
					: pt_type_call(Z_OBJ_P(extension), PT_LC("gettypefromstaticmethodcall"), 3, args);
				if (UNEXPECTED(resolvedType.isUndef())) return zv::Val();
				if (Z_TYPE_P(resolvedType.raw()) == IS_NULL) continue;

				resolvedTypes.push(std::move(resolvedType));
				handledClassNames.push(zv::Ref(className));
			}
		}

		if (zend_hash_num_elements(resolvedTypes.table()) == 0) return zv::Val::null();

		if (zend_hash_num_elements(Z_ARRVAL_P(allClassNames.raw())) != zend_hash_num_elements(handledClassNames.table())) {
			zv::Val remainingType = zv::Val::copyOf(zv::Ref(typeWithMethod));
			for (zv::ArrayEntry handled : zv::ArrRef(handledClassNames.raw())) {
				zval *handledClassName = handled.value().raw();
				if (UNEXPECTED(Z_TYPE_P(handledClassName) != IS_STRING)) {
					zend_type_error("PHPStan\\Type\\ObjectType::__construct(): Argument #1 ($className) must be of type string, %s given", zend_zval_value_name(handledClassName));
					return zv::Val();
				}
				zval objectType;
				if (UNEXPECTED(!pt_object_type_new(&objectType, Z_STR_P(handledClassName)))) return zv::Val();
				zv::Val handledType = zv::Val::adopt(objectType);
				zv::Val removed = pt_type_combinator_remove(remainingType.raw(), handledType.raw());
				if (UNEXPECTED(removed.isUndef())) return zv::Val();
				remainingType = std::move(removed);
			}
			zend_long hasMethod = pt_type_op_trinary(Z_OBJ_P(remainingType.raw()), PT_OP_HAS_METHOD, 1, methodName);
			if (UNEXPECTED(hasMethod < 0)) return zv::Val();
			if (hasMethod == PT_TRI_YES) {
				zv::Val remainingMethod = getMethod(remainingType.raw(), methodName, scope);
				if (UNEXPECTED(remainingMethod.isUndef())) return zv::Val();
				zv::Val remainingParametersAcceptor = selectFromArgs(scope, methodCall, remainingMethod.raw());
				if (UNEXPECTED(remainingParametersAcceptor.isUndef())) return zv::Val();
				zv::Val remainingReturnType = returnTypeOfCall(remainingParametersAcceptor.raw(), scope, methodCall);
				if (UNEXPECTED(remainingReturnType.isUndef())) return zv::Val();
				resolvedTypes.push(std::move(remainingReturnType));
			}
		}

		returned = true;
		HashTable *types = resolvedTypes.table();
		if (EXPECTED(HT_IS_PACKED(types) && HT_IS_WITHOUT_HOLES(types))) return pt_type_combinator_union(zend_hash_num_elements(types), types->arPacked);
		return pt_type_combinator_call_spread(PT_LC("union"), types);
	}
};

} // namespace phpstanturbo

using phpstanturbo::MethodCallReturnTypeHelper;

/* {{{ direct entries (support.h) */

zv::Val pt_method_call_return_type_helper_method_call_return_type(zval *helper, zval *scope, zval *typeWithMethod, zval *methodName, zval *methodCall, zval *preResolvedAcceptor, zval *argsResult)
{
	if (preResolvedAcceptor != NULL && Z_TYPE_P(preResolvedAcceptor) == IS_NULL) preResolvedAcceptor = NULL;
	if (argsResult != NULL && Z_TYPE_P(argsResult) == IS_NULL) argsResult = NULL;
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_method_call_return_type_helper && Z_TYPE_P(methodName) == IS_STRING)) {
		return MethodCallReturnTypeHelper(Z_OBJ_P(helper)).methodCallReturnType(scope, typeWithMethod, Z_STR_P(methodName), methodCall, preResolvedAcceptor, argsResult);
	}
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, typeWithMethod, methodName, methodCall, preResolvedAcceptor != NULL ? preResolvedAcceptor : &null, argsResult != NULL ? argsResult : &null};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("methodcallreturntype"), 6, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_method_call_return_type_helper()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\MethodCallReturnTypeHelper");
	ptdecl::MethodCallReturnTypeHelper::declareClass(cls);
	ptdecl::MethodCallReturnTypeHelper::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly (README rule 6) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *dynamicReturnTypeExtensionRegistry, *storagePrimer;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, dynamicReturnTypeExtensionRegistry, storagePrimer)) RETURN_THROWS();
		MethodCallReturnTypeHelper::construct(Z_OBJ_P(ZEND_THIS), dynamicReturnTypeExtensionRegistry, storagePrimer);
	});

	cls.method(sigs::methodCallReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *typeWithMethod, *methodCall;
		zend_string *methodName;
		zval *preResolvedAcceptor = NULL, *argsResult = NULL;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Str, zp::Obj, zp::Opt<zp::ObjOrNull>, zp::Opt<zp::ObjOrNull>>(execute_data, scope, typeWithMethod, methodName, methodCall, preResolvedAcceptor, argsResult)) RETURN_THROWS();
		PT_RETURN_VAL(MethodCallReturnTypeHelper(Z_OBJ_P(ZEND_THIS)).methodCallReturnType(scope, typeWithMethod, methodName, methodCall, preResolvedAcceptor, argsResult));
	});

	cls.shadow(&pt_ce_method_call_return_type_helper);
}

/* }}} */
