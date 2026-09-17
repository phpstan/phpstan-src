/*
 * PHPStanTurbo\ContextualClosureParameterResolver — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\ContextualClosureParameterResolver.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. hasIntrinsicArgs() and resolve() are exported
 * as pt_contextual_closure_parameter_resolver_has_intrinsic_args() /
 * pt_contextual_closure_parameter_resolver_resolve(); the latter hands native
 * callers the two parameter lists without the ClosureParameterTypes object the
 * public method returns. The twin's `static fn (ParameterReflection ...)`
 * array_map() callback is spelled out inline; its UnionType::filterTypes()
 * callback is a native closure (the method stays polymorphic).
 */

#include "support.h"
#include "generated/ContextualClosureParameterResolver.h"

namespace slots = ptdecl::ContextualClosureParameterResolver::slot;
namespace sigs = ptdecl::ContextualClosureParameterResolver::sig;
#include "ClosureSupport.h"

zend_class_entry *pt_ce_contextual_closure_parameter_resolver = nullptr;

namespace {

/* the attribute names (ArrayMapArgVisitor::ATTRIBUTE_NAME,
 * ImmediatelyInvokedClosureVisitor::ARGS_ATTRIBUTE_NAME) and 'item',
 * permanent interned strings (module startup) */
zend_string *pt_ccpr_array_map_args = nullptr;
zend_string *pt_ccpr_immediately_invoked_args = nullptr;
zend_string *pt_ccpr_item = nullptr;


/* static fn (Type $innerType) => $innerType->isCallable()->yes() */
void isCallableYesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) captures;
	if (UNEXPECTED(!ptcall::requireArguments(argc, 1, "PHPStan\\Analyser\\ExprHandler\\Helper\\ContextualClosureParameterResolver::{closure}"))) return;
	if (UNEXPECTED(Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\ContextualClosureParameterResolver::{closure}(): Argument #1 ($innerType) must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(&argv[0]));
		return;
	}
	zend_long isCallable = pt_type_op_trinary(Z_OBJ(argv[0]), PT_OP_IS_CALLABLE, 0, NULL);
	if (UNEXPECTED(isCallable < 0)) return;
	ZVAL_BOOL(return_value, isCallable == PT_TRI_YES);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\ContextualClosureParameterResolver;
 * UNDEF / false = pending exception. */
class ContextualClosureParameterResolver
{
public:
	explicit ContextualClosureParameterResolver(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *nodeScopeResolver)
	{
		zv::ObjRef(self).propAtWrite(slots::nodeScopeResolver, zv::Val::copyOf(zv::Ref(nodeScopeResolver)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::nodeScopeResolver)) = 0;
	}

	/* Mirrors hasIntrinsicArgs() */
	static bool hasIntrinsicArgs(zval *expr)
	{
		zval *arrayMapArgs = ptclosure::attribute(expr, pt_ccpr_array_map_args);
		if (arrayMapArgs != NULL && Z_TYPE_P(arrayMapArgs) != IS_NULL) return true;
		zval *immediatelyInvokedArgs = ptclosure::attribute(expr, pt_ccpr_immediately_invoked_args);
		return immediatelyInvokedArgs != NULL && Z_TYPE_P(immediatelyInvokedArgs) != IS_NULL;
	}

	/* Mirrors resolve(): the two lists of the ClosureParameterTypes
	 * ($storage / $passedToType / $nativePassedToType NULL for null) */
	[[nodiscard]] bool resolve(zval *scope, zval *expr, zval *storage, zval *passedToType, zval *nativePassedToType, zv::Val &parameters, zv::Val &nativeParameters) const
	{
		zval *arrayMapArgs = ptclosure::attribute(expr, pt_ccpr_array_map_args);
		if (arrayMapArgs != NULL && Z_TYPE_P(arrayMapArgs) == IS_NULL) arrayMapArgs = NULL;
		zval *intrinsicArgs = arrayMapArgs;
		if (intrinsicArgs == NULL) {
			intrinsicArgs = ptclosure::attribute(expr, pt_ccpr_immediately_invoked_args);
			if (intrinsicArgs != NULL && Z_TYPE_P(intrinsicArgs) == IS_NULL) intrinsicArgs = NULL;
		}
		if (EXPECTED(intrinsicArgs == NULL)) {
			parameters = createPassedToTypeParameters(scope, passedToType);
			if (UNEXPECTED(parameters.isUndef())) return false;
			nativeParameters = createPassedToTypeParameters(scope, nativePassedToType);
			return !nativeParameters.isUndef();
		}

		zv::Val argsHold = zv::Val::copyOf(zv::Ref(intrinsicArgs));
		if (UNEXPECTED(Z_TYPE_P(argsHold.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(argsHold.raw()));
			if (UNEXPECTED(EG(exception))) return false;
			parameters = zv::Val(zv::Arr::empty());
			nativeParameters = zv::Val(zv::Arr::empty());
			return true;
		}
		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(argsHold.raw()));
		zv::Arr parameterList = count > 0 ? zv::Arr::create(count) : zv::Arr::empty();
		zv::Arr nativeParameterList = count > 0 ? zv::Arr::create(count) : zv::Arr::empty();
		zval *nodeScopeResolver = OBJ_PROP_NUM(self, slots::nodeScopeResolver);
		zend_object *no = pt_passed_by_reference_create_no();
		if (UNEXPECTED(no == NULL)) return false;
		zval noZv;
		ZVAL_OBJ(&noZv, no);
		for (zv::ArrayEntry entry : zv::ArrRef(argsHold.raw())) {
			zval *value = ptclosure::prop(ptclosure::argValueSite, entry.value().deref().raw(), PT_LC("value"));
			if (UNEXPECTED(value == NULL)) return false;
			zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
			zv::Val result = zv::Val::null();
			if (storage != NULL) {
				result = pt_expression_result_storage_find(storage, valueHold.raw());
				if (UNEXPECTED(result.isUndef())) return false;
			}
			zv::Val type = !result.isNull() ? pt_expression_result_get_type(result.raw()) : pt_node_scope_resolver_read_scope_state_or_synthetic_type(nodeScopeResolver, valueHold.raw(), scope);
			if (UNEXPECTED(type.isUndef())) return false;
			zv::Val nativeType;
			if (!result.isNull()) {
				nativeType = pt_expression_result_get_native_type(result.raw());
			} else {
				zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
				if (UNEXPECTED(nativeScope.isUndef())) return false;
				nativeType = pt_node_scope_resolver_read_scope_state_or_synthetic_type(nodeScopeResolver, valueHold.raw(), nativeScope.raw());
			}
			if (UNEXPECTED(nativeType.isUndef())) return false;
			if (arrayMapArgs != NULL) {
				if (UNEXPECTED(!type.ref().isObject() || !nativeType.ref().isObject())) {
					zend_throw_error(NULL, "Call to a member function getIterableValueType() on %s", zend_zval_value_name(type.ref().isObject() ? nativeType.raw() : type.raw()));
					return false;
				}
				type = pt_type_op(Z_OBJ_P(type.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
				if (UNEXPECTED(type.isUndef())) return false;
				nativeType = pt_type_op(Z_OBJ_P(nativeType.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
				if (UNEXPECTED(nativeType.isUndef())) return false;
			}
			zv::Val parameter = pt_dummy_parameter_new(pt_ccpr_item, type.raw(), false, &noZv, false, NULL);
			if (UNEXPECTED(parameter.isUndef())) return false;
			parameterList.push(std::move(parameter));
			zv::Val nativeParameter = pt_dummy_parameter_new(pt_ccpr_item, nativeType.raw(), false, &noZv, false, NULL);
			if (UNEXPECTED(nativeParameter.isUndef())) return false;
			nativeParameterList.push(std::move(nativeParameter));
		}
		parameters = zv::Val(std::move(parameterList));
		nativeParameters = zv::Val(std::move(nativeParameterList));
		return true;
	}

private:
	zend_object *self;

	/* Mirrors createPassedToTypeParameters() ($passedToType NULL for null):
	 * the list or null */
	static zv::Val createPassedToTypeParameters(zval *scope, zval *passedToType)
	{
		if (EXPECTED(passedToType == NULL)) return zv::Val::null();
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(passedToType), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable == PT_TRI_NO) return zv::Val::null();

		zv::Val filtered;
		if (instanceof_function(Z_OBJCE_P(passedToType), pt_ce_union_type)) {
			zv::Val filter = pt_native_closure(&isCallableYesBody);
			filtered = pt_type_call(Z_OBJ_P(passedToType), PT_LC("filtertypes"), 1, filter.raw());
			if (UNEXPECTED(filtered.isUndef())) return zv::Val();
			if (UNEXPECTED(!filtered.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isCallable() on %s", zend_zval_value_name(filtered.raw()));
				return zv::Val();
			}
			passedToType = filtered.raw();
			isCallable = pt_type_op_trinary(Z_OBJ_P(passedToType), PT_OP_IS_CALLABLE, 0, NULL);
			if (UNEXPECTED(isCallable < 0)) return zv::Val();
			if (isCallable == PT_TRI_NO) return zv::Val::null();
		}

		zv::Val acceptors = pt_type_call(Z_OBJ_P(passedToType), PT_LC("getcallableparametersacceptors"), 1, scope);
		if (UNEXPECTED(acceptors.isUndef())) return zv::Val();
		if (UNEXPECTED(!acceptors.ref().isArray())) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(acceptors.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}
		zv::Val callableParameters = zv::Val::null();
		for (zv::ArrayEntry entry : zv::ArrRef(acceptors.raw())) {
			zval *acceptor = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(acceptor) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getParameters() on %s", zend_zval_value_name(acceptor));
				return zv::Val();
			}
			zv::Val acceptorParameters = mapToNativeParameters(acceptor);
			if (UNEXPECTED(acceptorParameters.isUndef())) return zv::Val();
			if (callableParameters.isNull()) {
				callableParameters = std::move(acceptorParameters);
				continue;
			}

			HashTable *current = Z_ARRVAL_P(callableParameters.raw());
			HashTable *next = Z_ARRVAL_P(acceptorParameters.raw());
			uint32_t currentCount = zend_hash_num_elements(current);
			uint32_t nextCount = zend_hash_num_elements(next);
			uint32_t parameterCount = currentCount > nextCount ? currentCount : nextCount;
			zv::Arr newParameters = parameterCount > 0 ? zv::Arr::create(parameterCount) : zv::Arr::empty();
			for (uint32_t i = 0; i < parameterCount; i++) {
				zval *acceptorParameter = zend_hash_index_find(next, i);
				zval *callableParameter = zend_hash_index_find(current, i);
				zv::Val merged;
				if (acceptorParameter == NULL) {
					merged = toOptional(callableParameter, i);
				} else if (callableParameter == NULL) {
					merged = toOptional(acceptorParameter, i);
				} else {
					merged = unionParameters(callableParameter, acceptorParameter);
				}
				if (UNEXPECTED(merged.isUndef())) return zv::Val();
				newParameters.push(std::move(merged));
			}
			callableParameters = zv::Val(std::move(newParameters));
		}
		return callableParameters;
	}

	/* array_map(static fn (ParameterReflection $callableParameter) => new
	 * NativeParameterReflection(...), $acceptor->getParameters()) — keys kept */
	static zv::Val mapToNativeParameters(zval *acceptor)
	{
		zv::Val parameters = pt_parameters_acceptor_call(acceptor, PT_PA_GET_PARAMETERS);
		if (UNEXPECTED(parameters.isUndef())) return zv::Val();
		if (UNEXPECTED(!parameters.ref().isArray())) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(parameters.raw()));
			return zv::Val();
		}
		HashTable *table = Z_ARRVAL_P(parameters.raw());
		uint32_t count = zend_hash_num_elements(table);
		zv::Arr mapped = count > 0 ? zv::Arr::create(count) : zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::TableRef(table)) {
			zval *parameter = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) {
				zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\ContextualClosureParameterResolver::{closure}(): Argument #1 ($callableParameter) must be of type PHPStan\\Reflection\\ParameterReflection, %s given", zend_zval_value_name(parameter));
				return zv::Val();
			}
			zv::Val nativeParameter = ptclosure::nativeParameterFrom(parameter, NULL);
			if (UNEXPECTED(nativeParameter.isUndef())) return zv::Val();
			if (entry.hasStringKey()) {
				mapped.set(entry.stringKey(), std::move(nativeParameter));
			} else {
				zval value = nativeParameter.take();
				mapped.separate();
				zend_hash_index_update(mapped.table(), entry.indexKey(), &value);
			}
		}
		return zv::Val(std::move(mapped));
	}

	/* $parameters[$i]->toOptional() of a NativeParameterReflection */
	static zv::Val toOptional(zval *parameter, uint32_t i)
	{
		(void) i;
		ZVAL_DEREF(parameter);
		return pt_native_parameter_reflection_to_optional(parameter);
	}

	/* $callableParameters[$i]->union($acceptorParameters[$i]) */
	static zv::Val unionParameters(zval *parameter, zval *other)
	{
		ZVAL_DEREF(parameter);
		ZVAL_DEREF(other);
		return pt_native_parameter_reflection_union(parameter, other);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ContextualClosureParameterResolver;

bool pt_contextual_closure_parameter_resolver_has_intrinsic_args(zval *resolver, zval *expr, bool &out)
{
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_contextual_closure_parameter_resolver)) {
		out = ContextualClosureParameterResolver::hasIntrinsicArgs(expr);
		return true;
	}
	zv::Val result = pt_type_call(Z_OBJ_P(resolver), PT_LC("hasintrinsicargs"), 1, expr);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

bool pt_contextual_closure_parameter_resolver_resolve(zval *resolver, zval *scope, zval *expr, zval *storage, zval *passedToType, zval *nativePassedToType, zv::Val &parameters, zv::Val &nativeParameters)
{
	if (storage != NULL && Z_TYPE_P(storage) == IS_NULL) storage = NULL;
	if (passedToType != NULL && Z_TYPE_P(passedToType) == IS_NULL) passedToType = NULL;
	if (nativePassedToType != NULL && Z_TYPE_P(nativePassedToType) == IS_NULL) nativePassedToType = NULL;
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_contextual_closure_parameter_resolver)) return ContextualClosureParameterResolver(Z_OBJ_P(resolver)).resolve(scope, expr, storage, passedToType, nativePassedToType, parameters, nativeParameters);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, expr, storage != NULL ? storage : &null, passedToType != NULL ? passedToType : &null, nativePassedToType != NULL ? nativePassedToType : &null};
	zv::Val parameterTypes = pt_type_call(Z_OBJ_P(resolver), PT_LC("resolve"), 5, argv);
	if (UNEXPECTED(parameterTypes.isUndef())) return false;
	return ptclosure::readClosureParameterTypes(parameterTypes.raw(), parameters, nativeParameters);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_contextual_closure_parameter_resolver()
{
	pt_ccpr_array_map_args = zend_string_init_interned(PT_LC("arrayMapArgs"), 1);
	pt_ccpr_immediately_invoked_args = zend_string_init_interned(PT_LC("immediatelyInvokedClosureArgs"), 1);
	pt_ccpr_item = zend_string_init_interned(PT_LC("item"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\ContextualClosureParameterResolver");
	ptdecl::ContextualClosureParameterResolver::declareClass(cls);
	ptdecl::ContextualClosureParameterResolver::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter class exactly */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver;
		if (!zp::parse<zp::Obj>(execute_data, nodeScopeResolver)) RETURN_THROWS();
		ContextualClosureParameterResolver(Z_OBJ_P(ZEND_THIS)).construct(nodeScopeResolver);
	});

	cls.method(sigs::hasIntrinsicArgs, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		RETURN_BOOL(ContextualClosureParameterResolver::hasIntrinsicArgs(expr));
	});

	cls.method(sigs::resolve, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *storage, *passedToType, *nativePassedToType;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(storage)
			Z_PARAM_OBJECT_OR_NULL(passedToType)
			Z_PARAM_OBJECT_OR_NULL(nativePassedToType)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val parameters, nativeParameters;
		if (UNEXPECTED(!ContextualClosureParameterResolver(Z_OBJ_P(ZEND_THIS)).resolve(scope, expr, storage, passedToType, nativePassedToType, parameters, nativeParameters))) RETURN_THROWS();
		PT_RETURN_VAL(ptclosure::newClosureParameterTypes(parameters.raw(), nativeParameters.raw()));
	});

	cls.shadow(&pt_ce_contextual_closure_parameter_resolver);
}

/* }}} */
