/*
 * PHPStanTurbo\ClosureParameterResolver — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\ClosureParameterResolver.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. resolve() and resolveCallableTypeForScope()
 * are exported as pt_closure_parameter_resolver_resolve() /
 * pt_closure_parameter_resolver_resolve_callable_type_for_scope(); resolve()
 * hands native callers the two parameter lists without the
 * ClosureParameterTypes object the public method returns. The twin's two
 * private `fn (MutatingScope $s, Expr $e): Type` type getters are the
 * `native` flag of createCallArgsParameters(). ContextualClosureParameterResolver,
 * ClosureTypeResolver, NodeScopeResolver, MutatingScope and
 * NativeParameterReflection are called through their direct entries.
 */

#include "support.h"
#include "generated/ClosureParameterResolver.h"

namespace slots = ptdecl::ClosureParameterResolver::slot;
namespace sigs = ptdecl::ClosureParameterResolver::sig;
#include "ClosureSupport.h"

zend_class_entry *pt_ce_closure_parameter_resolver = nullptr;

namespace {


} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\ClosureParameterResolver;
 * UNDEF / false = pending exception. */
class ClosureParameterResolver
{
public:
	explicit ClosureParameterResolver(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *nodeScopeResolver, zval *closureTypeResolver, zval *contextualClosureParameterResolver)
	{
		writeSlot(slots::nodeScopeResolver, nodeScopeResolver);
		writeSlot(slots::closureTypeResolver, closureTypeResolver);
		writeSlot(slots::contextualClosureParameterResolver, contextualClosureParameterResolver);
	}

	/* Mirrors resolve(): the two lists of the ClosureParameterTypes
	 * ($storage / $callArgs / $passedToType / $nativePassedToType NULL for
	 * null) */
	[[nodiscard]] bool resolve(zval *scope, zval *expr, zval *storage, zval *callArgs, zval *passedToType, zval *nativePassedToType, zv::Val &parameters, zv::Val &nativeParameters) const
	{
		zval *contextual = OBJ_PROP_NUM(self, slots::contextualClosureParameterResolver);
		bool intrinsic = callArgs == NULL;
		if (!intrinsic && UNEXPECTED(!pt_contextual_closure_parameter_resolver_has_intrinsic_args(contextual, expr, intrinsic))) return false;
		if (intrinsic) {
			return pt_contextual_closure_parameter_resolver_resolve(contextual, scope, expr, storage, passedToType, nativePassedToType, parameters, nativeParameters);
		}

		/* the arguments are node attributes a nested closure type's walk may
		 * rehash (freeVariableRoots() writes one): held for both passes */
		zv::Val callArgsHold = zv::Val::copyOf(zv::Ref(callArgs));
		parameters = createCallArgsParameters(scope, expr, callArgsHold.raw(), false);
		if (UNEXPECTED(parameters.isUndef())) return false;
		nativeParameters = createCallArgsParameters(scope, expr, callArgsHold.raw(), true);
		return !nativeParameters.isUndef();
	}

	/* Mirrors resolveCallableTypeForScope() */
	zv::Val resolveCallableTypeForScope(zval *expr, zval *scope) const
	{
		int isClosure = ptclosure::instanceOf(expr, PT_CLASS_CLOSURE_EXPR);
		if (UNEXPECTED(isClosure < 0)) return zv::Val();
		if (!isClosure) {
			isClosure = ptclosure::instanceOf(expr, PT_CLASS_ARROW_FUNCTION);
			if (UNEXPECTED(isClosure < 0)) return zv::Val();
		}
		if (isClosure) {
			zv::Val storage = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(scope));
			if (UNEXPECTED(storage.isUndef())) return zv::Val();
			return pt_closure_type_resolver_get_closure_type(OBJ_PROP_NUM(self, slots::closureTypeResolver), scope, expr, false, storage.raw());
		}

		return pt_node_scope_resolver_read_type_of_maybe_stored(OBJ_PROP_NUM(self, slots::nodeScopeResolver), expr, scope);
	}

private:
	zend_object *self;

	void writeSlot(uint32_t index, zval *value)
	{
		zv::ObjRef(self).propAtWrite(index, zv::Val::copyOf(zv::Ref(value)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, index)) = 0;
	}

	/* $typeGetter($scope, $expr) of the twin's two getters:
	 * resolveCallableTypeForScope($e, $s) / ($e, $s->doNotTreatPhpDocTypesAsCertain()) */
	zv::Val typeOf(zval *scope, zval *expr, bool native) const
	{
		if (UNEXPECTED(Z_TYPE_P(expr) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureParameterResolver::{closure}(): Argument #2 ($e) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(expr));
			return zv::Val();
		}
		if (!native) return resolveCallableTypeForScope(expr, scope);
		zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
		if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
		return resolveCallableTypeForScope(expr, nativeScope.raw());
	}

	/* $args[$index]->value (borrowed); NULL = pending exception */
	static zval *argValueAt(HashTable *args, zend_ulong index, zv::Val &hold)
	{
		zval *arg = zend_hash_index_find(args, index);
		if (UNEXPECTED(arg == NULL)) {
			zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, index);
			if (UNEXPECTED(EG(exception))) return NULL;
			zend_error(E_WARNING, "Attempt to read property \"value\" on null");
			if (UNEXPECTED(EG(exception))) return NULL;
			return &EG(uninitialized_zval);
		}
		ZVAL_DEREF(arg);
		zval *value = ptclosure::prop(ptclosure::argValueSite, arg, PT_LC("value"));
		if (UNEXPECTED(value == NULL)) return NULL;
		hold = zv::Val::copyOf(zv::Ref(value));
		return hold.raw();
	}

	/* Mirrors createCallArgsParameters() with the getter chosen by native:
	 * the list or null */
	zv::Val createCallArgsParameters(zval *scope, zval *closureExpr, zval *args, bool native) const
	{
		zv::Val closureType = typeOf(scope, closureExpr, native);
		if (UNEXPECTED(closureType.isUndef())) return zv::Val();
		if (UNEXPECTED(!closureType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isCallable() on %s", zend_zval_value_name(closureType.raw()));
			return zv::Val();
		}
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(closureType.raw()), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable == PT_TRI_NO) return zv::Val::null();

		zv::Val acceptors = pt_type_call(Z_OBJ_P(closureType.raw()), PT_LC("getcallableparametersacceptors"), 1, scope);
		if (UNEXPECTED(acceptors.isUndef())) return zv::Val();
		if (UNEXPECTED(!acceptors.ref().isArray())) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(acceptors.raw()));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(acceptors.raw())) != 1) return zv::Val::null();

		zval *acceptor = zend_hash_index_find(Z_ARRVAL_P(acceptors.raw()), 0);
		if (UNEXPECTED(acceptor == NULL)) {
			zend_error(E_WARNING, "Undefined array key 0");
			if (UNEXPECTED(EG(exception))) return zv::Val();
			zend_throw_error(NULL, "Call to a member function getParameters() on null");
			return zv::Val();
		}
		ZVAL_DEREF(acceptor);
		if (UNEXPECTED(Z_TYPE_P(acceptor) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getParameters() on %s", zend_zval_value_name(acceptor));
			return zv::Val();
		}
		zv::Val callableParameters = pt_parameters_acceptor_call(acceptor, PT_PA_GET_PARAMETERS);
		if (UNEXPECTED(callableParameters.isUndef())) return zv::Val();
		if (UNEXPECTED(!callableParameters.ref().isArray())) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(callableParameters.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return callableParameters;
		}

		HashTable *argsTable = Z_ARRVAL_P(args);
		/* foreach iterates the list as it was, the assignments go to a separated copy */
		zv::Val iterated = zv::Val::copyOf(zv::Ref(callableParameters.raw()));
		zv::Arr result = zv::Arr::adoptVal(std::move(callableParameters));
		for (zv::ArrayEntry entry : zv::ArrRef(iterated.raw())) {
			zend_string *stringKey = entry.stringKeyOrNull();
			zend_ulong index = entry.indexKey();
			/* isset($args[$index]) */
			zval *present = stringKey != NULL ? zend_symtable_find(argsTable, stringKey) : zend_hash_index_find(argsTable, index);
			if (present != NULL) {
				ZVAL_DEREF(present);
			}
			if (present == NULL || Z_TYPE_P(present) == IS_NULL) continue;
			if (UNEXPECTED(stringKey != NULL)) {
				/* a list has no string keys; `$j = $index` would not count */
				zend_type_error("Unsupported operand types: string < int");
				return zv::Val();
			}

			zval *callableParameter = entry.value().deref().raw();
			zv::Val isVariadic = ptclosure::parameterIsVariadic(callableParameter);
			if (UNEXPECTED(isVariadic.isUndef())) return zv::Val();
			zv::Val type;
			if (zend_is_true(isVariadic.raw())) {
				zv::Arr argTypes = zv::Arr::empty();
				zend_ulong argNumber = zend_hash_num_elements(argsTable);
				for (zend_ulong j = index; j < argNumber; j++) {
					zv::Val valueHold;
					zval *value = argValueAt(argsTable, j, valueHold);
					if (UNEXPECTED(value == NULL)) return zv::Val();
					zv::Val argType = typeOf(scope, value, native);
					if (UNEXPECTED(argType.isUndef())) return zv::Val();
					argTypes.push(std::move(argType));
				}
				type = ptclosure::unionOf(argTypes);
			} else {
				zv::Val valueHold;
				zval *value = argValueAt(argsTable, index, valueHold);
				if (UNEXPECTED(value == NULL)) return zv::Val();
				type = typeOf(scope, value, native);
			}
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zv::Val nativeParameter = ptclosure::nativeParameterFrom(callableParameter, type.raw());
			if (UNEXPECTED(nativeParameter.isUndef())) return zv::Val();
			result.separate();
			zval item = nativeParameter.take();
			zend_hash_index_update(result.table(), index, &item);
		}
		return zv::Val(std::move(result));
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureParameterResolver;

bool pt_closure_parameter_resolver_resolve(zval *resolver, zval *scope, zval *expr, zval *storage, zval *callArgs, zval *passedToType, zval *nativePassedToType, zv::Val &parameters, zv::Val &nativeParameters)
{
	if (storage != NULL && Z_TYPE_P(storage) == IS_NULL) storage = NULL;
	if (passedToType != NULL && Z_TYPE_P(passedToType) == IS_NULL) passedToType = NULL;
	if (nativePassedToType != NULL && Z_TYPE_P(nativePassedToType) == IS_NULL) nativePassedToType = NULL;
	if (callArgs != NULL && Z_TYPE_P(callArgs) == IS_NULL) callArgs = NULL;
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_closure_parameter_resolver && (callArgs == NULL || Z_TYPE_P(callArgs) == IS_ARRAY))) return ClosureParameterResolver(Z_OBJ_P(resolver)).resolve(scope, expr, storage, callArgs, passedToType, nativePassedToType, parameters, nativeParameters);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, expr, storage != NULL ? storage : &null, callArgs != NULL ? callArgs : &null, passedToType != NULL ? passedToType : &null, nativePassedToType != NULL ? nativePassedToType : &null};
	zv::Val parameterTypes = pt_type_call(Z_OBJ_P(resolver), PT_LC("resolve"), 6, argv);
	if (UNEXPECTED(parameterTypes.isUndef())) return false;
	return ptclosure::readClosureParameterTypes(parameterTypes.raw(), parameters, nativeParameters);
}

zv::Val pt_closure_parameter_resolver_resolve_callable_type_for_scope(zval *resolver, zval *expr, zval *scope)
{
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_closure_parameter_resolver)) return ClosureParameterResolver(Z_OBJ_P(resolver)).resolveCallableTypeForScope(expr, scope);
	zv::Args argv{expr, scope};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("resolvecallabletypeforscope"), 2, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_closure_parameter_resolver()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureParameterResolver");
	ptdecl::ClosureParameterResolver::declareClass(cls);
	ptdecl::ClosureParameterResolver::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *closureTypeResolver, *contextualClosureParameterResolver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, nodeScopeResolver, closureTypeResolver, contextualClosureParameterResolver)) RETURN_THROWS();
		ClosureParameterResolver(Z_OBJ_P(ZEND_THIS)).construct(nodeScopeResolver, closureTypeResolver, contextualClosureParameterResolver);
	});

	cls.method(sigs::resolve, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *storage, *callArgs, *passedToType, *nativePassedToType;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(storage)
			Z_PARAM_ARRAY_OR_NULL(callArgs)
			Z_PARAM_OBJECT_OR_NULL(passedToType)
			Z_PARAM_OBJECT_OR_NULL(nativePassedToType)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val parameters, nativeParameters;
		if (UNEXPECTED(!ClosureParameterResolver(Z_OBJ_P(ZEND_THIS)).resolve(scope, expr, storage, callArgs, passedToType, nativePassedToType, parameters, nativeParameters))) RETURN_THROWS();
		PT_RETURN_VAL(ptclosure::newClosureParameterTypes(parameters.raw(), nativeParameters.raw()));
	});

	cls.method(sigs::resolveCallableTypeForScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *scope;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expr, scope)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureParameterResolver(Z_OBJ_P(ZEND_THIS)).resolveCallableTypeForScope(expr, scope));
	});

	cls.shadow(&pt_ce_closure_parameter_resolver);
}

/* }}} */
