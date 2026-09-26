/*
 * PHPStanTurbo\TemplateArgumentObserver — native implementation of
 * PHPStan\Analyser\Generics\TemplateArgumentObserver.
 *
 * The stateless DI service matching declared and actual types for template
 * arguments a call left unresolved. collectSites() runs on the type of every
 * call the observation pass walks (~90K per self-analysis) and its
 * TypeTraverser callback on every node of those types, so it is a native
 * callback holder entered by the native traverser without a frame; the
 * matching of sends and lower bounds (a few thousand calls) asks the Type
 * kernel through its ops, ClassReflection through its entries and the rest
 * of the template machinery by name. The twin's closures over $this and
 * `&$constraints` are native closures over the same captures. Native callers
 * use the pt_template_argument_observer_* entries (support.h).
 */

#include "support.h"
#include "generated/TemplateArgumentObserver.h"
#include "generated/UnresolvedTemplateArgumentType.h"

namespace sigs = ptdecl::TemplateArgumentObserver::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AcceptorValues.h"
#include "ParameterValues.h"

zend_class_entry *pt_ce_template_argument_observer = nullptr;

namespace {

/* {{{ type tests and reads */

inline bool isMarker(zval *type)
{
	return Z_TYPE_P(type) == IS_OBJECT && Z_OBJCE_P(type) == pt_ce_unresolved_template_argument_type;
}

inline bool isInstance(zval *type, zend_class_entry *ce)
{
	return Z_TYPE_P(type) == IS_OBJECT && instanceof_function(Z_OBJCE_P(type), ce);
}

/* $type instanceof TemplateType; false = pending exception */
[[nodiscard]] bool isTemplateType(zval *type, bool &out)
{
	zend_class_entry *ce = pt_class_loaded(PT_CLASS_TEMPLATE_TYPE);
	if (UNEXPECTED(EG(exception))) return false;
	out = ce != NULL && isInstance(type, ce);
	return true;
}

/* $type instanceof UnionType && !$type instanceof TemplateType; false =
 * pending exception */
[[nodiscard]] bool isPlainUnion(zval *type, bool &out)
{
	if (!isInstance(type, pt_ce_union_type)) {
		out = false;
		return true;
	}
	bool isTemplate;
	if (UNEXPECTED(!isTemplateType(type, isTemplate))) return false;
	out = !isTemplate;
	return true;
}

/* $type instanceof MixedType && !$type instanceof TemplateType; false =
 * pending exception */
[[nodiscard]] bool isPlainMixed(zval *type, bool &out)
{
	if (!isInstance(type, pt_ce_mixed_type)) {
		out = false;
		return true;
	}
	bool isTemplate;
	if (UNEXPECTED(!isTemplateType(type, isTemplate))) return false;
	out = !isTemplate;
	return true;
}

/* $marker->getInitialType() of an UnresolvedTemplateArgumentType */
zv::Val markerInitialType(zval *marker)
{
	zval *initial = OBJ_PROP_NUM(Z_OBJ_P(marker), ptdecl::UnresolvedTemplateArgumentType::slot::initialType);
	if (EXPECTED(Z_TYPE_P(initial) != IS_UNDEF)) return zv::Val::copyOf(zv::Ref(initial));
	return pt_type_call(Z_OBJ_P(marker), PT_LC("getinitialtype"), 0, NULL);
}

/* $object->method() by name, the engine's Error for a non-object */
zv::Val call0(zval *object, const char *lcname, size_t len, const char *method)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(object), lcname, len, 0, NULL);
}

zv::Val call1(zval *object, const char *lcname, size_t len, const char *method, zval *argument)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(object), lcname, len, 1, argument);
}

/* $type->isX()->yes() of a TrinaryLogic query by name; false = pending exception */
[[nodiscard]] bool trinaryYes(zval *type, const char *lcname, size_t len, bool &out)
{
	zend_long value = pt_type_call_trinary(Z_OBJ_P(type), lcname, len, 0, NULL);
	if (UNEXPECTED(value < 0)) return false;
	out = value == PT_TRI_YES;
	return true;
}

/* $variance->invariant() / ->covariant() / ->contravariant() / ->bivariant();
 * false = pending exception */
[[nodiscard]] bool varianceIs(zval *variance, zend_long expected, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(variance) != IS_OBJECT)) {
		const char *name = expected == PT_TEMPLATE_TYPE_VARIANCE_INVARIANT ? "invariant" : (expected == PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT ? "contravariant" : "bivariant");
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(variance));
		return false;
	}
	zend_long value;
	if (UNEXPECTED(!pt_template_type_variance_value_of(variance, value))) return false;
	out = value == expected;
	return true;
}

/* $list[$i] of a list read by an integer key, NULL when absent */
inline zval *listItem(zval *list, zend_ulong index)
{
	if (UNEXPECTED(Z_TYPE_P(list) != IS_ARRAY)) return NULL;
	zval *item = zend_hash_index_find(Z_ARRVAL_P(list), index);
	if (item != NULL) {
		ZVAL_DEREF(item);
	}
	return item;
}

/* $type->getObjectClassReflections() */
zv::Val objectClassReflections(zval *type)
{
	return pt_type_op(Z_OBJ_P(type), PT_OP_GET_OBJECT_CLASS_REFLECTIONS, 0, NULL);
}

inline uint32_t countOf(zval *array)
{
	return Z_TYPE_P(array) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(array)) : 0;
}

/* }}} */

void collectSitesBody(zval *constraints, zval *state1, uint32_t argc, zval *argv, zval *return_value);
void containsMarkerBody(zval *contains, zval *state1, uint32_t argc, zval *argv, zval *return_value);
void containsTemplateArgumentMarkerBody(zval *contains, zval *state1, uint32_t argc, zval *argv, zval *return_value);
void containsClosureSignatureMarkerBody(zval *contains, zval *state1, uint32_t argc, zval *argv, zval *return_value);
void escapeClosuresBody(zval *constraints, zval *state1, uint32_t argc, zval *argv, zval *return_value);
void replaceInferableTemplatesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value);

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\Generics\TemplateArgumentObserver (stateless);
 * the constraints flow through by value as the twin passes them; UNDEF =
 * pending exception. */
class TemplateArgumentObserver
{
public:
	/* Mirrors collectSites(). */
	static zv::Val collectSites(zval *type)
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		zv::Val callback = pt_type_native_callback(collectSitesBody, constraints.raw(), NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		constraints.release();
		zv::Val mapped = pt_type_traverser_map_of(type, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		return zv::Val::copyOf(zv::Ref(pt_type_native_callback_state(callback.raw(), 0)));
	}

	/* Mirrors containsMarker(); false = pending exception */
	[[nodiscard]] static bool containsMarker(zval *type, bool &out, bool templateArgumentsOnly = false)
	{
		zval contains;
		ZVAL_FALSE(&contains);
		zv::Val callback = pt_type_native_callback(templateArgumentsOnly ? containsTemplateArgumentMarkerBody : containsMarkerBody, &contains, NULL);
		if (UNEXPECTED(callback.isUndef())) return false;
		zv::Val mapped = pt_type_traverser_map_of(type, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return false;
		out = Z_TYPE_P(pt_type_native_callback_state(callback.raw(), 0)) == IS_TRUE;
		return true;
	}

	/* Mirrors collectSend(). */
	static zv::Val collectSend(zval *declared, zval *actual)
	{
		zv::Val constraints = observeSend(pt_template_argument_constraints_create_empty(), declared, actual, false);

		return observeClosureSend(std::move(constraints), declared, actual);
	}

	/* Mirrors collectClosureArgument(). */
	static zv::Val collectClosureArgument(zval *parameterType, zval *argumentType)
	{
		return observeClosureSend(pt_template_argument_constraints_create_empty(), parameterType, argumentType);
	}

	/* Mirrors collectClosureArguments(). */
	static zv::Val collectClosureArguments(zval *acceptor, zval *argumentTypes, bool isPure)
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		zv::Val parameters;
		zv::Arr parametersByName = zv::Arr::create(0);
		uint32_t parameterCount = 0;
		for (auto entry : zv::ArrRef(argumentTypes)) {
			zval *argumentType = entry.value().deref().raw();
			bool contains;
			if (UNEXPECTED(!containsClosureSignatureMarker(argumentType, contains))) return zv::Val();
			if (!contains) continue;
			if (parameters.isUndef()) {
				parameters = pt_parameters_acceptor_call(acceptor, PT_PA_GET_PARAMETERS);
				if (UNEXPECTED(parameters.isUndef())) return zv::Val();
				for (auto parameterEntry : zv::ArrRef(parameters.raw())) {
					zval *parameter = parameterEntry.value().deref().raw();
					zv::Val name = pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
					if (UNEXPECTED(name.isUndef())) return zv::Val();
					zval *slot = Z_TYPE_P(name.raw()) == IS_STRING
						? zend_symtable_update(parametersByName.table(), Z_STR_P(name.raw()), parameter)
						: zend_hash_index_update(parametersByName.table(), (zend_ulong) zval_get_long(name.raw()), parameter);
					Z_TRY_ADDREF_P(slot);
				}
				parameterCount = countOf(parameters.raw());
			}
			zval *parameter;
			zend_string *key = entry.stringKeyOrNull();
			if (key != NULL) {
				parameter = zend_hash_find(parametersByName.table(), key);
			} else {
				parameter = listItem(parameters.raw(), entry.indexKey());
			}
			if (parameter != NULL) {
				ZVAL_DEREF(parameter);
			}
			if (parameter == NULL || Z_TYPE_P(parameter) == IS_NULL) {
				bool variadic;
				if (UNEXPECTED(!pt_parameters_acceptor_bool(acceptor, PT_PA_IS_VARIADIC, variadic))) return zv::Val();
				parameter = variadic && parameterCount > 0 ? listItem(parameters.raw(), parameterCount - 1) : NULL;
			}
			if (parameter == NULL || Z_TYPE_P(parameter) == IS_NULL) {
				constraints = escapeClosures(std::move(constraints), argumentType);
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				continue;
			}
			zv::Val parameterHold = zv::Val::copyOf(zv::Ref(parameter));
			zv::Val parameterType = pt_parameter_reflection_call(parameterHold.raw(), PT_PR_GET_TYPE);
			if (UNEXPECTED(parameterType.isUndef())) return zv::Val();
			if (isPure) {
				bool plainMixed;
				if (UNEXPECTED(!isPlainMixed(parameterType.raw(), plainMixed))) return zv::Val();
				if (plainMixed) continue;
			}
			constraints = observeClosureSend(std::move(constraints), parameterType.raw(), argumentType);
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}

		return constraints;
	}

	/* Mirrors collectEscape(). */
	static zv::Val collectEscape(zval *type)
	{
		bool contains;
		if (UNEXPECTED(!containsClosureSignatureMarker(type, contains))) return zv::Val();
		if (!contains) return pt_template_argument_constraints_create_empty();

		return escapeClosures(pt_template_argument_constraints_create_empty(), type);
	}

	/* Mirrors carriesClosureSignatureMarkers(); false = pending exception */
	[[nodiscard]] static bool carriesClosureSignatureMarkers(zval *types, bool &out)
	{
		out = false;
		for (auto entry : zv::ArrRef(types)) {
			if (UNEXPECTED(!containsClosureSignatureMarker(entry.value().deref().raw(), out))) return false;
			if (out) return true;
		}
		return true;
	}

	/* Mirrors containsClosureSignatureMarker(); false = pending exception */
	[[nodiscard]] static bool containsClosureSignatureMarker(zval *type, bool &out)
	{
		zval contains;
		ZVAL_FALSE(&contains);
		zv::Val callback = pt_type_native_callback(containsClosureSignatureMarkerBody, &contains, NULL);
		if (UNEXPECTED(callback.isUndef())) return false;
		zv::Val mapped = pt_type_traverser_map_of(type, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return false;
		out = Z_TYPE_P(pt_type_native_callback_state(callback.raw(), 0)) == IS_TRUE;
		return true;
	}

	/* Mirrors observeClosureSend(). */
	static zv::Val observeClosureSend(zv::Val constraints, zval *declared, zval *actual)
	{
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		bool contains;
		if (UNEXPECTED(!containsClosureSignatureMarker(actual, contains))) return zv::Val();
		if (!contains) return constraints;
		if (isInstance(actual, pt_ce_union_type)) {
			zv::Val types = pt_type_op(Z_OBJ_P(actual), PT_OP_GET_TYPES, 0, NULL);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			for (auto entry : zv::ArrRef(types.raw())) {
				constraints = observeClosureSend(std::move(constraints), declared, entry.value().deref().raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			}
			return constraints;
		}
		if (isInstance(actual, pt_ce_closure_type)) {
			return observeClosureSendToCallable(std::move(constraints), declared, actual);
		}
		bool actualObject, actualIterable;
		if (UNEXPECTED(!trinaryYes(actual, PT_LC("isobject"), actualObject))) return zv::Val();
		if (!actualObject) {
			if (UNEXPECTED(!trinaryYes(actual, PT_LC("isiterable"), actualIterable))) return zv::Val();
			if (actualIterable) {
				bool declaredObject;
				if (UNEXPECTED(!trinaryYes(declared, PT_LC("isobject"), declaredObject))) return zv::Val();
				if (!declaredObject) {
					bool declaredIterable;
					if (UNEXPECTED(!trinaryYes(declared, PT_LC("isiterable"), declaredIterable))) return zv::Val();
					if (declaredIterable) {
						zv::Val declaredKey = pt_type_op(Z_OBJ_P(declared), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
						if (UNEXPECTED(declaredKey.isUndef())) return zv::Val();
						zv::Val actualKey = pt_type_op(Z_OBJ_P(actual), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
						if (UNEXPECTED(actualKey.isUndef())) return zv::Val();
						constraints = observeClosureSend(std::move(constraints), declaredKey.raw(), actualKey.raw());
						if (UNEXPECTED(constraints.isUndef())) return zv::Val();
						zv::Val declaredValue = pt_type_op(Z_OBJ_P(declared), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
						if (UNEXPECTED(declaredValue.isUndef())) return zv::Val();
						zv::Val actualValue = pt_type_op(Z_OBJ_P(actual), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
						if (UNEXPECTED(actualValue.isUndef())) return zv::Val();
						return observeClosureSend(std::move(constraints), declaredValue.raw(), actualValue.raw());
					}
				}
			}
		}

		return escapeClosures(std::move(constraints), actual);
	}

	/* Mirrors observeClosureSendToCallable(). */
	static zv::Val observeClosureSendToCallable(zv::Val constraints, zval *declaredArg, zval *actual)
	{
		zv::Val declared = zv::Val::copyOf(zv::Ref(declaredArg));
		bool declaredTemplate;
		if (UNEXPECTED(!isTemplateType(declared.raw(), declaredTemplate))) return zv::Val();
		if (isInstance(declared.raw(), pt_ce_union_type) && !declaredTemplate) {
			zv::Val types = pt_type_op(Z_OBJ_P(declared.raw()), PT_OP_GET_TYPES, 0, NULL);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			zv::Arr kept = zv::Arr::create(0);
			for (auto entry : zv::ArrRef(types.raw())) {
				zval *member = entry.value().deref().raw();
				zend_long callable = pt_type_call_trinary(Z_OBJ_P(member), PT_LC("iscallable"), 0, NULL);
				if (UNEXPECTED(callable < 0)) return zv::Val();
				if (callable == PT_TRI_NO) continue;
				kept.push(zv::Val::copyOf(zv::Ref(member)));
			}
			HashTable *keptTable = kept.table();
			uint32_t keptCount = zend_hash_num_elements(keptTable);
			zval *keptArgv = keptCount > 0 ? (zval *) safe_emalloc(keptCount, sizeof(zval), 0) : NULL;
			uint32_t k = 0;
			for (auto entry : zv::ArrRef(kept.raw())) {
				ZVAL_COPY_VALUE(&keptArgv[k++], entry.value().raw());
			}
			declared = pt_type_combinator_union(keptCount, keptArgv);
			if (keptArgv != NULL) efree(keptArgv);
			if (UNEXPECTED(declared.isUndef())) return zv::Val();
		}
		bool declaredMixed = isInstance(declared.raw(), pt_ce_mixed_type);
		bool declaredCallable = false;
		if (!declaredMixed && UNEXPECTED(!trinaryYes(declared.raw(), PT_LC("iscallable"), declaredCallable))) return zv::Val();
		if (declaredMixed || !declaredCallable) {
			return escapeClosures(std::move(constraints), actual);
		}

		zv::Val closureParameters = pt_type_call(Z_OBJ_P(actual), PT_LC("getparameters"), 0, NULL);
		if (UNEXPECTED(closureParameters.isUndef())) return zv::Val();
		zv::Val returnMarker = pt_type_call(Z_OBJ_P(actual), PT_LC("getreturntype"), 0, NULL);
		if (UNEXPECTED(returnMarker.isUndef())) return zv::Val();
		zv::Val outOfClassScope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
		if (UNEXPECTED(outOfClassScope.isUndef())) return zv::Val();
		zv::Val acceptors = pt_type_call(Z_OBJ_P(declared.raw()), PT_LC("getcallableparametersacceptors"), 1, outOfClassScope.raw());
		if (UNEXPECTED(acceptors.isUndef())) return zv::Val();
		for (auto acceptorEntry : zv::ArrRef(acceptors.raw())) {
			zv::Val acceptor = zv::Val::copyOf(acceptorEntry.value().deref());
			zv::Val targetParameters = pt_parameters_acceptor_call(acceptor.raw(), PT_PA_GET_PARAMETERS);
			if (UNEXPECTED(targetParameters.isUndef())) return zv::Val();
			uint32_t targetCount = countOf(targetParameters.raw());
			bool acceptorVariadic;
			if (UNEXPECTED(!pt_parameters_acceptor_bool(acceptor.raw(), PT_PA_IS_VARIADIC, acceptorVariadic))) return zv::Val();
			if (targetCount == 0 && acceptorVariadic) {
				/* callable, Closure: the parameters are not described */
				constraints = escapeClosures(std::move(constraints), actual);
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				continue;
			}
			for (auto parameterEntry : zv::ArrRef(closureParameters.raw())) {
				zval *closureParameter = parameterEntry.value().deref().raw();
				zv::Val marker = pt_parameter_reflection_call(closureParameter, PT_PR_GET_TYPE);
				if (UNEXPECTED(marker.isUndef())) return zv::Val();
				if (!isMarker(marker.raw())) continue;
				bool closureMarker;
				if (UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_signature(marker.raw(), closureMarker))) return zv::Val();
				if (!closureMarker) continue;
				zend_ulong i = parameterEntry.indexKey();
				bool variadic;
				if (UNEXPECTED(!pt_parameter_reflection_bool(closureParameter, PT_PR_IS_VARIADIC, variadic))) return zv::Val();
				if (variadic) {
					for (zend_ulong j = i; j < targetCount; j++) {
						zval *target = listItem(targetParameters.raw(), j);
						if (target == NULL) continue;
						zv::Val targetType = pt_parameter_reflection_call(target, PT_PR_GET_TYPE);
						if (UNEXPECTED(targetType.isUndef())) return zv::Val();
						constraints = pt_template_argument_constraints_with_lower_bound(constraints.raw(), marker.raw(), targetType.raw());
						if (UNEXPECTED(constraints.isUndef())) return zv::Val();
					}
					continue;
				}
				zval *target = listItem(targetParameters.raw(), i);
				if (target != NULL && Z_TYPE_P(target) != IS_NULL) {
					zv::Val targetType = pt_parameter_reflection_call(target, PT_PR_GET_TYPE);
					if (UNEXPECTED(targetType.isUndef())) return zv::Val();
					constraints = pt_template_argument_constraints_with_lower_bound(constraints.raw(), marker.raw(), targetType.raw());
					if (UNEXPECTED(constraints.isUndef())) return zv::Val();
					continue;
				}
				if (!acceptorVariadic || targetCount == 0) {
					/* never passed: the closure parameter keeps its default */
					continue;
				}
				zval *last = listItem(targetParameters.raw(), targetCount - 1);
				if (last == NULL) continue;
				zv::Val lastType = pt_parameter_reflection_call(last, PT_PR_GET_TYPE);
				if (UNEXPECTED(lastType.isUndef())) return zv::Val();
				constraints = pt_template_argument_constraints_with_lower_bound(constraints.raw(), marker.raw(), lastType.raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			}

			zv::Val targetReturnType = pt_parameters_acceptor_call(acceptor.raw(), PT_PA_GET_RETURN_TYPE);
			if (UNEXPECTED(targetReturnType.isUndef())) return zv::Val();
			zv::Val returnedType;
			bool closureReturn = false;
			if (isMarker(returnMarker.raw()) && UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_return(returnMarker.raw(), closureReturn))) return zv::Val();
			if (closureReturn) {
				bool isVoid;
				if (UNEXPECTED(!trinaryYes(targetReturnType.raw(), PT_LC("isvoid"), isVoid))) return zv::Val();
				if (!isVoid && !isInstance(targetReturnType.raw(), pt_ce_mixed_type)) {
					zv::Val covariant = pt_type_template_type_variance(PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
					if (UNEXPECTED(covariant.isUndef())) return zv::Val();
					constraints = pt_template_argument_constraints_with_send(constraints.raw(), returnMarker.raw(), targetReturnType.raw(), covariant.raw());
					if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				}
				returnedType = markerInitialType(returnMarker.raw());
				if (UNEXPECTED(returnedType.isUndef())) return zv::Val();
			} else {
				returnedType = zv::Val::copyOf(zv::Ref(returnMarker.raw()));
			}
			if (Z_TYPE_P(returnedType.raw()) != IS_OBJECT) continue;

			/* a closure returning closures: the returned ones are sent on */
			constraints = observeClosureSend(std::move(constraints), targetReturnType.raw(), returnedType.raw());
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}

		return constraints;
	}

	/* Mirrors escapeClosures(). */
	static zv::Val escapeClosures(zv::Val constraints, zval *type)
	{
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Val callback = pt_type_native_callback(escapeClosuresBody, constraints.raw(), NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zv::Val mapped = pt_type_traverser_map_of(type, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		return zv::Val::copyOf(zv::Ref(pt_type_native_callback_state(callback.raw(), 0)));
	}

	/* Mirrors collectArgument(). */
	static zv::Val collectArgument(zval *parameterType, zval *argumentType, bool isPure)
	{
		if (isPure) {
			bool plainMixed;
			if (UNEXPECTED(!isPlainMixed(parameterType, plainMixed))) return zv::Val();
			if (plainMixed) return pt_template_argument_constraints_create_empty();
		}
		return observeArgument(pt_template_argument_constraints_create_empty(), parameterType, argumentType);
	}

	/* Mirrors collectCall() ($classTemplates NULL / IS_NULL for null). */
	static zv::Val collectCall(zval *self, zval *site, zval *acceptorZv, zval *argumentTypes, zval *classTemplates)
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		zv::Val acceptor = zv::Val::copyOf(zv::Ref(acceptorZv));
		zend_class_entry *resolvedCe = pt_class_loaded(PT_CLASS_RESOLVED_FUNCTION_VARIANT);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		if (resolvedCe != NULL && isInstance(acceptor.raw(), resolvedCe)) {
			acceptor = pt_parameters_acceptor_call(acceptor.raw(), PT_PA_GET_ORIGINAL_PARAMETERS_ACCEPTOR);
			if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
		}

		/* new TemplateTypeMap(array_merge($classTemplates?->getTypes() ?? [], $acceptor->getTemplateTypeMap()->getTypes())) */
		zv::Arr merged = zv::Arr::create(0);
		if (classTemplates != NULL && Z_TYPE_P(classTemplates) != IS_NULL) {
			zv::Val classTypes = pt_template_type_map_get_types(classTemplates);
			if (UNEXPECTED(classTypes.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_callable_array_merge_into(merged, classTypes.raw()))) return zv::Val();
		}
		zv::Val templateTypeMap = pt_parameters_acceptor_call(acceptor.raw(), PT_PA_GET_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(templateTypeMap.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getTypes() on %s", zend_zval_value_name(templateTypeMap.raw()));
			return zv::Val();
		}
		zv::Val acceptorTypes = pt_template_type_map_get_types(templateTypeMap.raw());
		if (UNEXPECTED(acceptorTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_callable_array_merge_into(merged, acceptorTypes.raw()))) return zv::Val();
		zval templatesZv;
		if (UNEXPECTED(!pt_template_type_map_new(&templatesZv, merged.raw()))) return zv::Val();
		zv::Val templates = zv::Val::adopt(templatesZv);
		zv::Val isEmpty = pt_type_call(Z_OBJ_P(templates.raw()), PT_LC("isempty"), 0, NULL);
		if (UNEXPECTED(isEmpty.isUndef())) return zv::Val();
		if (zend_is_true(isEmpty.raw())) return constraints;

		bool hasMarkers = false;
		for (auto entry : zv::ArrRef(argumentTypes)) {
			bool contains;
			/* a closure whose signature is being inferred is sent through
			 * collectClosureArguments(), it holds no template argument */
			if (UNEXPECTED(!containsMarker(entry.value().deref().raw(), contains, true))) return zv::Val();
			if (!contains) continue;
			hasMarkers = true;
			break;
		}
		if (!hasMarkers) return constraints;

		zv::Val parameters = pt_parameters_acceptor_call(acceptor.raw(), PT_PA_GET_PARAMETERS);
		if (UNEXPECTED(parameters.isUndef())) return zv::Val();
		zv::Arr parametersByName = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(parameters.raw())) {
			zval *parameter = entry.value().deref().raw();
			zv::Val name = pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zval *slot = Z_TYPE_P(name.raw()) == IS_STRING
				? zend_symtable_update(parametersByName.table(), Z_STR_P(name.raw()), parameter)
				: zend_hash_index_update(parametersByName.table(), (zend_ulong) zval_get_long(name.raw()), parameter);
			Z_TRY_ADDREF_P(slot);
		}
		uint32_t parameterCount = countOf(parameters.raw());

		for (auto entry : zv::ArrRef(argumentTypes)) {
			zval *argumentType = entry.value().deref().raw();
			zval *parameter;
			zend_string *key = entry.stringKeyOrNull();
			if (key != NULL) {
				parameter = zend_hash_find(parametersByName.table(), key);
			} else {
				parameter = listItem(parameters.raw(), entry.indexKey());
			}
			if (parameter != NULL) {
				ZVAL_DEREF(parameter);
			}
			zv::Val parameterHold;
			if (parameter == NULL || Z_TYPE_P(parameter) == IS_NULL) {
				bool variadic;
				if (UNEXPECTED(!pt_parameters_acceptor_bool(acceptor.raw(), PT_PA_IS_VARIADIC, variadic))) return zv::Val();
				parameter = variadic && parameterCount > 0 ? listItem(parameters.raw(), parameterCount - 1) : NULL;
				if (parameter != NULL) {
					parameterHold = zv::Val::copyOf(zv::Ref(parameter));
					parameter = parameterHold.raw();
				}
			} else {
				parameterHold = zv::Val::copyOf(zv::Ref(parameter));
				parameter = parameterHold.raw();
			}
			if (parameter == NULL || Z_TYPE_P(parameter) == IS_NULL) continue;

			zv::Val parameterType = pt_parameter_reflection_call(parameter, PT_PR_GET_TYPE);
			if (UNEXPECTED(parameterType.isUndef())) return zv::Val();
			zval reference;
			ZVAL_NEW_REF(&reference, constraints.raw());
			ZVAL_UNDEF(constraints.raw());
			zv::Val referenceHold = zv::Val::adopt(reference);
			zv::Val replaced = replaceInferableTemplates(self, parameterType.raw(), site, templates.raw(), referenceHold.raw());
			constraints = zv::Val::copyOf(zv::Ref(Z_REFVAL_P(referenceHold.raw())));
			if (UNEXPECTED(replaced.isUndef())) return zv::Val();
			constraints = observeArgument(std::move(constraints), replaced.raw(), argumentType);
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}

		return constraints;
	}

	/* Mirrors replaceInferableTemplates(); $constraints is the reference the
	 * twin's by-reference parameter binds (an IS_REFERENCE zval). */
	static zv::Val replaceInferableTemplates(zval *self, zval *type, zval *site, zval *templates, zval *constraintsReference)
	{
		bool plainUnion;
		if (UNEXPECTED(!isPlainUnion(type, plainUnion))) return zv::Val();
		if (plainUnion) {
			zv::Val types = pt_type_op(Z_OBJ_P(type), PT_OP_GET_TYPES, 0, NULL);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			uint32_t count = countOf(types.raw());
			zv::Arr members = zv::Arr::create(count);
			zv::Arr naked = zv::Arr::create(0);
			zv::Arr structural = zv::Arr::create(0);
			for (auto entry : zv::ArrRef(types.raw())) {
				zv::Val member = replaceInferableTemplates(self, entry.value().deref().raw(), site, templates, constraintsReference);
				if (UNEXPECTED(member.isUndef())) return zv::Val();
				members.push(zv::Ref(member.raw()));
				if (isMarker(member.raw())) {
					naked.push(zv::Ref(member.raw()));
					continue;
				}
				bool contains;
				if (UNEXPECTED(!containsMarker(member.raw(), contains))) return zv::Val();
				if (contains) structural.push(zv::Ref(member.raw()));
			}
			if (zend_hash_num_elements(naked.table()) == 0 || zend_hash_num_elements(structural.table()) == 0) {
				uint32_t argc = zend_hash_num_elements(members.table());
				zval *argv = (zval *) emalloc(sizeof(zval) * (argc > 0 ? argc : 1));
				uint32_t i = 0;
				for (auto entry : zv::ArrRef(members.raw())) {
					ZVAL_COPY_VALUE(&argv[i++], entry.value().raw());
				}
				zv::Val union_ = pt_type_combinator_union(argc, argv);
				efree(argv);
				return union_;
			}
			for (auto entry : zv::ArrRef(structural.raw())) {
				naked.push(entry.value());
			}
			zval result;
			if (UNEXPECTED(!pt_union_type_new(&result, naked.raw()))) return zv::Val();
			return zv::Val::adopt(result);
		}

		zval captures[4];
		ZVAL_COPY_VALUE(&captures[0], self);
		ZVAL_COPY_VALUE(&captures[1], site);
		ZVAL_COPY_VALUE(&captures[2], templates);
		ZVAL_COPY_VALUE(&captures[3], constraintsReference);
		zv::Val callback = pt_native_closure_new(&replaceInferableTemplatesBody, 4, captures, 1u << 3);
		return pt_type_traverser_map_of(type, callback.raw());
	}

	/* Mirrors observeSend(). */
	static zv::Val observeSend(zv::Val constraints, zval *declared, zval *actual, bool isCallArgument)
	{
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		bool declaredTemplate;
		if (UNEXPECTED(!isTemplateType(declared, declaredTemplate))) return zv::Val();
		if (declaredTemplate) return constraints;
		bool contains;
		if (UNEXPECTED(!containsMarker(actual, contains))) return zv::Val();
		if (!contains) return constraints;

		if (isCallArgument && isInstance(declared, pt_ce_mixed_type)) {
			zv::Val sites = collectSites(actual);
			if (UNEXPECTED(sites.isUndef())) return zv::Val();
			struct Data
			{
				zv::Val *constraints;
			} data{&constraints};
			bool ok = pt_template_argument_constraints_facts(sites.raw(), [](void *raw, zval *fact) -> bool {
				Data *d = static_cast<Data *>(raw);
				/* foreach (... as [$marker]) */
				zval *marker = Z_TYPE_P(fact) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(fact), 0) : NULL;
				zval null;
				ZVAL_NULL(&null);
				if (marker == NULL) {
					if (Z_TYPE_P(fact) == IS_ARRAY) zend_error(E_WARNING, "Undefined array key 0");
					marker = &null;
				}
				ZVAL_DEREF(marker);
				zv::Val next = pt_template_argument_constraints_with_unconstraining_send(d->constraints->raw(), marker);
				if (UNEXPECTED(next.isUndef())) return false;
				*d->constraints = std::move(next);
				return true;
			}, &data);
			if (UNEXPECTED(!ok)) return zv::Val();
			return constraints;
		}

		bool actualUnion = isInstance(actual, pt_ce_union_type);
		if (actualUnion) {
			zv::Val types = pt_type_op(Z_OBJ_P(actual), PT_OP_GET_TYPES, 0, NULL);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			for (auto entry : zv::ArrRef(types.raw())) {
				constraints = observeSend(std::move(constraints), declared, entry.value().deref().raw(), isCallArgument);
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			}
			return constraints;
		}
		if (isInstance(declared, pt_ce_union_type)) {
			zv::Val types = pt_type_op(Z_OBJ_P(declared), PT_OP_GET_TYPES, 0, NULL);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			for (auto entry : zv::ArrRef(types.raw())) {
				constraints = observeSend(std::move(constraints), entry.value().deref().raw(), actual, isCallArgument);
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			}
			return constraints;
		}
		if (isMarker(actual)) return constraints;
		if (isInstance(actual, pt_ce_never_type)) return constraints;

		zv::Val actualReflections = objectClassReflections(actual);
		if (UNEXPECTED(actualReflections.isUndef())) return zv::Val();
		uint32_t actualReflectionCount = countOf(actualReflections.raw());
		if (actualReflectionCount == 1) {
			zv::Val declaredReflections = objectClassReflections(declared);
			if (UNEXPECTED(declaredReflections.isUndef())) return zv::Val();
			if (countOf(declaredReflections.raw()) != 1) return constraints;
			zval *declaredReflection = listItem(declaredReflections.raw(), 0);
			zval *actualReflection = listItem(actualReflections.raw(), 0);
			if (UNEXPECTED(declaredReflection == NULL || actualReflection == NULL)) return undefinedKeyZero();

			zv::Val declaredName = reflectionName(declaredReflection);
			if (UNEXPECTED(declaredName.isUndef())) return zv::Val();
			zv::Val ancestor = call1(actualReflection, PT_LC("getancestorwithclassname"), "getAncestorWithClassName", declaredName.raw());
			if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
			if (Z_TYPE_P(ancestor.raw()) == IS_NULL) return constraints;
			bool generic;
			if (UNEXPECTED(!reflectionIsGeneric(ancestor.raw(), generic))) return zv::Val();
			if (!generic) return constraints;

			zv::Val templates = reflectionTypeMapList(ancestor.raw(), PT_LC("gettemplatetypemap"), "getTemplateTypeMap");
			if (UNEXPECTED(templates.isUndef())) return zv::Val();
			zv::Val declaredArguments = reflectionTypeMapList(declaredReflection, PT_LC("getpossiblyincompleteactivetemplatetypemap"), "getPossiblyIncompleteActiveTemplateTypeMap");
			if (UNEXPECTED(declaredArguments.isUndef())) return zv::Val();
			zv::Val declaredVariances = call0(declaredReflection, PT_LC("getcallsitevariancemap"), "getCallSiteVarianceMap");
			if (UNEXPECTED(declaredVariances.isUndef())) return zv::Val();
			zv::Val arguments = reflectionTypeMapList(ancestor.raw(), PT_LC("getactivetemplatetypemap"), "getActiveTemplateTypeMap");
			if (UNEXPECTED(arguments.isUndef())) return zv::Val();

			for (auto entry : zv::ArrRef(arguments.raw())) {
				zval *argument = entry.value().deref().raw();
				zval *template_ = entry.hasStringKey() ? zend_hash_find(Z_ARRVAL_P(templates.raw()), entry.stringKey()) : listItem(templates.raw(), entry.indexKey());
				if (template_ != NULL) {
					ZVAL_DEREF(template_);
				}
				bool templateIsTemplate = false;
				if (template_ != NULL && UNEXPECTED(!isTemplateType(template_, templateIsTemplate))) return zv::Val();
				zval *declaredArgumentSlot = entry.hasStringKey() ? zend_hash_find(Z_ARRVAL_P(declaredArguments.raw()), entry.stringKey()) : listItem(declaredArguments.raw(), entry.indexKey());
				if (declaredArgumentSlot != NULL) {
					ZVAL_DEREF(declaredArgumentSlot);
				}
				if (!templateIsTemplate || declaredArgumentSlot == NULL || Z_TYPE_P(declaredArgumentSlot) == IS_NULL) continue;
				zv::Val declaredArgument = zv::Val::copyOf(zv::Ref(declaredArgumentSlot));
				if (!isMarker(argument)) {
					constraints = observeSend(std::move(constraints), declaredArgument.raw(), argument, isCallArgument);
					if (UNEXPECTED(constraints.isUndef())) return zv::Val();
					continue;
				}
				if (isCallArgument) {
					zv::Val initial = markerInitialType(argument);
					if (UNEXPECTED(initial.isUndef())) return zv::Val();
					bool emptyInitial = Z_TYPE_P(initial.raw()) == IS_NULL;
					if (!emptyInitial) {
						zv::Val again = markerInitialType(argument);
						if (UNEXPECTED(again.isUndef())) return zv::Val();
						emptyInitial = isInstance(again.raw(), pt_ce_never_type);
					}
					if (emptyInitial) {
						bool onlyInferable;
						if (UNEXPECTED(!hasOnlyInferableTemplates(declaredArgument.raw(), onlyInferable))) return zv::Val();
						if (onlyInferable) {
							declaredArgument = pt_type_call_static_ce(pt_ce_template_type_helper, PT_LC("resolvetodefaults"), 1, declaredArgument.raw());
							if (UNEXPECTED(declaredArgument.isUndef())) return zv::Val();
						}
					}
				}
				bool uninformative;
				if (UNEXPECTED(!isUninformativeSendTarget(declaredArgument.raw(), uninformative))) return zv::Val();
				if (uninformative) {
					bool unconstraining = false;
					if (isCallArgument) {
						if (UNEXPECTED(!hasOnlyInferableTemplates(declaredArgument.raw(), unconstraining))) return zv::Val();
					}
					if (!unconstraining) {
						if (UNEXPECTED(!isPlainMixed(declaredArgument.raw(), unconstraining))) return zv::Val();
					}
					if (unconstraining) {
						constraints = pt_template_argument_constraints_with_unconstraining_send(constraints.raw(), argument);
						if (UNEXPECTED(constraints.isUndef())) return zv::Val();
					}
					continue;
				}

				/* $declaredVariances->getVariance($template->getName()) ?? TemplateTypeVariance::createInvariant() */
				zv::Val templateName = call0(template_, PT_LC("getname"), "getName");
				if (UNEXPECTED(templateName.isUndef())) return zv::Val();
				zv::Val callSiteVariance = call1(declaredVariances.raw(), PT_LC("getvariance"), "getVariance", templateName.raw());
				if (UNEXPECTED(callSiteVariance.isUndef())) return zv::Val();
				if (Z_TYPE_P(callSiteVariance.raw()) == IS_NULL) {
					zval *invariant = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_INVARIANT);
					if (UNEXPECTED(invariant == NULL)) return zv::Val();
					callSiteVariance = zv::Val::copyOf(zv::Ref(invariant));
				}
				bool invariant;
				if (UNEXPECTED(!varianceIs(callSiteVariance.raw(), PT_TEMPLATE_TYPE_VARIANCE_INVARIANT, invariant))) return zv::Val();
				zv::Val effectiveVariance = invariant ? call0(template_, PT_LC("getvariance"), "getVariance") : std::move(callSiteVariance);
				if (UNEXPECTED(effectiveVariance.isUndef())) return zv::Val();
				constraints = pt_template_argument_constraints_with_send(constraints.raw(), argument, declaredArgument.raw(), effectiveVariance.raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();

				zv::Val initial = markerInitialType(argument);
				if (UNEXPECTED(initial.isUndef())) return zv::Val();
				if (Z_TYPE_P(initial.raw()) == IS_NULL) continue;
				constraints = observeSend(std::move(constraints), declaredArgument.raw(), initial.raw(), isCallArgument);
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			}

			return constraints;
		}

		if (actualReflectionCount > 0) return constraints;
		bool yes;
		if (UNEXPECTED(!trinaryYes(actual, PT_LC("isobject"), yes))) return zv::Val();
		if (yes) return constraints;
		if (UNEXPECTED(!trinaryYes(actual, PT_LC("isiterable"), yes))) return zv::Val();
		if (!yes) return constraints;
		if (UNEXPECTED(!trinaryYes(declared, PT_LC("isiterable"), yes))) return zv::Val();
		if (!yes) return constraints;

		zv::Val declaredKey = pt_type_op(Z_OBJ_P(declared), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(declaredKey.isUndef())) return zv::Val();
		zv::Val actualKey = pt_type_op(Z_OBJ_P(actual), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(actualKey.isUndef())) return zv::Val();
		constraints = observeSend(std::move(constraints), declaredKey.raw(), actualKey.raw(), isCallArgument);
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Val declaredValue = pt_type_op(Z_OBJ_P(declared), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(declaredValue.isUndef())) return zv::Val();
		zv::Val actualValue = pt_type_op(Z_OBJ_P(actual), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(actualValue.isUndef())) return zv::Val();
		return observeSend(std::move(constraints), declaredValue.raw(), actualValue.raw(), isCallArgument);
	}

	/* Mirrors observeArgument(). */
	static zv::Val observeArgument(zv::Val constraints, zval *parameterType, zval *argumentType)
	{
		constraints = observeSend(std::move(constraints), parameterType, argumentType, true);
		return observeLowerBound(std::move(constraints), parameterType, argumentType);
	}

	/* Mirrors observeLowerBound(). */
	static zv::Val observeLowerBound(zv::Val constraints, zval *parameterType, zval *argumentType)
	{
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		bool contains;
		if (UNEXPECTED(!containsMarker(parameterType, contains))) return zv::Val();
		if (!contains) return constraints;
		if (isMarker(parameterType)) return pt_template_argument_constraints_with_lower_bound(constraints.raw(), parameterType, argumentType);
		if (isInstance(parameterType, pt_ce_never_type)) return constraints;
		bool parameterTemplate;
		if (UNEXPECTED(!isTemplateType(parameterType, parameterTemplate))) return zv::Val();
		if (parameterTemplate) return constraints;
		zend_long callable = pt_type_op_trinary(Z_OBJ_P(parameterType), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(callable < 0)) return zv::Val();
		if (callable == PT_TRI_YES) return constraints;

		if (isInstance(parameterType, pt_ce_union_type)) {
			zv::Val argumentMembers;
			if (isInstance(argumentType, pt_ce_union_type)) {
				argumentMembers = pt_type_op(Z_OBJ_P(argumentType), PT_OP_GET_TYPES, 0, NULL);
				if (UNEXPECTED(argumentMembers.isUndef())) return zv::Val();
			} else {
				zv::Arr single = zv::Arr::create(1);
				single.push(zv::Ref(argumentType));
				argumentMembers = zv::Val(std::move(single));
			}
			for (auto argumentEntry : zv::ArrRef(argumentMembers.raw())) {
				zval *argumentMember = argumentEntry.value().deref().raw();
				bool taken = false;
				zv::Val members = pt_type_op(Z_OBJ_P(parameterType), PT_OP_GET_TYPES, 0, NULL);
				if (UNEXPECTED(members.isUndef())) return zv::Val();
				for (auto entry : zv::ArrRef(members.raw())) {
					zval *member = entry.value().deref().raw();
					if (isMarker(member)) continue;
					bool memberContains;
					if (UNEXPECTED(!containsMarker(member, memberContains))) return zv::Val();
					if (!memberContains) {
						zv::Val superType = pt_type_op(Z_OBJ_P(member), PT_OP_IS_SUPER_TYPE_OF, 1, argumentMember);
						if (UNEXPECTED(superType.isUndef())) return zv::Val();
						zend_long verdict = pt_type_result_trinary(superType.raw());
						if (UNEXPECTED(verdict < 0)) return zv::Val();
						if (verdict == PT_TRI_YES) taken = true;
						continue;
					}
					zv::Val before = zv::Val::copyOf(zv::Ref(constraints.raw()));
					constraints = observeLowerBound(std::move(constraints), member, argumentMember);
					if (UNEXPECTED(constraints.isUndef())) return zv::Val();
					if (Z_TYPE_P(constraints.raw()) == IS_OBJECT && Z_TYPE_P(before.raw()) == IS_OBJECT && Z_OBJ_P(constraints.raw()) == Z_OBJ_P(before.raw())) continue;
					taken = true;
				}
				if (taken) continue;
				zv::Val again = pt_type_op(Z_OBJ_P(parameterType), PT_OP_GET_TYPES, 0, NULL);
				if (UNEXPECTED(again.isUndef())) return zv::Val();
				for (auto entry : zv::ArrRef(again.raw())) {
					zval *member = entry.value().deref().raw();
					if (!isMarker(member)) continue;
					constraints = observeLowerBound(std::move(constraints), member, argumentMember);
					if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				}
			}
			return constraints;
		}
		if (isInstance(argumentType, pt_ce_union_type)) {
			zv::Val types = pt_type_op(Z_OBJ_P(argumentType), PT_OP_GET_TYPES, 0, NULL);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			for (auto entry : zv::ArrRef(types.raw())) {
				constraints = observeLowerBound(std::move(constraints), parameterType, entry.value().deref().raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			}
			return constraints;
		}

		zv::Val parameterReflections = objectClassReflections(parameterType);
		if (UNEXPECTED(parameterReflections.isUndef())) return zv::Val();
		uint32_t parameterReflectionCount = countOf(parameterReflections.raw());
		if (parameterReflectionCount == 1) {
			zval *parameterReflection = listItem(parameterReflections.raw(), 0);
			if (UNEXPECTED(parameterReflection == NULL)) return undefinedKeyZero();
			bool generic;
			if (UNEXPECTED(!reflectionIsGeneric(parameterReflection, generic))) return zv::Val();
			if (!generic) return constraints;
			zv::Val argumentReflections = objectClassReflections(argumentType);
			if (UNEXPECTED(argumentReflections.isUndef())) return zv::Val();
			if (countOf(argumentReflections.raw()) != 1) return constraints;
			zval *argumentReflection = listItem(argumentReflections.raw(), 0);
			if (UNEXPECTED(argumentReflection == NULL)) return undefinedKeyZero();
			zv::Val parameterName = reflectionName(parameterReflection);
			if (UNEXPECTED(parameterName.isUndef())) return zv::Val();
			zv::Val ancestor = call1(argumentReflection, PT_LC("getancestorwithclassname"), "getAncestorWithClassName", parameterName.raw());
			if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
			if (Z_TYPE_P(ancestor.raw()) == IS_NULL) return constraints;
			zv::Val ancestorArguments = reflectionTypeMapList(ancestor.raw(), PT_LC("getactivetemplatetypemap"), "getActiveTemplateTypeMap");
			if (UNEXPECTED(ancestorArguments.isUndef())) return zv::Val();
			zv::Val parameterArguments = reflectionTypeMapList(parameterReflection, PT_LC("getactivetemplatetypemap"), "getActiveTemplateTypeMap");
			if (UNEXPECTED(parameterArguments.isUndef())) return zv::Val();
			for (auto entry : zv::ArrRef(parameterArguments.raw())) {
				zval *parameterArgument = entry.value().deref().raw();
				zval *ancestorArgument = entry.hasStringKey() ? zend_hash_find(Z_ARRVAL_P(ancestorArguments.raw()), entry.stringKey()) : listItem(ancestorArguments.raw(), entry.indexKey());
				if (ancestorArgument != NULL) {
					ZVAL_DEREF(ancestorArgument);
				}
				if (ancestorArgument == NULL || Z_TYPE_P(ancestorArgument) == IS_NULL) continue;
				if (isMarker(parameterArgument)) {
					zv::Val templates = reflectionTypeMapList(parameterReflection, PT_LC("gettemplatetypemap"), "getTemplateTypeMap");
					if (UNEXPECTED(templates.isUndef())) return zv::Val();
					zval *template_ = entry.hasStringKey() ? zend_hash_find(Z_ARRVAL_P(templates.raw()), entry.stringKey()) : listItem(templates.raw(), entry.indexKey());
					if (template_ != NULL) {
						ZVAL_DEREF(template_);
					}
					bool templateIsTemplate = false;
					if (template_ != NULL && UNEXPECTED(!isTemplateType(template_, templateIsTemplate))) return zv::Val();
					if (templateIsTemplate) {
						zv::Val varianceMap = call0(parameterReflection, PT_LC("getcallsitevariancemap"), "getCallSiteVarianceMap");
						if (UNEXPECTED(varianceMap.isUndef())) return zv::Val();
						zv::Val templateName = call0(template_, PT_LC("getname"), "getName");
						if (UNEXPECTED(templateName.isUndef())) return zv::Val();
						zv::Val variance = call1(varianceMap.raw(), PT_LC("getvariance"), "getVariance", templateName.raw());
						if (UNEXPECTED(variance.isUndef())) return zv::Val();
						if (Z_TYPE_P(variance.raw()) == IS_NULL) {
							zval *invariantSingleton = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_INVARIANT);
							if (UNEXPECTED(invariantSingleton == NULL)) return zv::Val();
							variance = zv::Val::copyOf(zv::Ref(invariantSingleton));
						}
						bool is;
						if (UNEXPECTED(!varianceIs(variance.raw(), PT_TEMPLATE_TYPE_VARIANCE_INVARIANT, is))) return zv::Val();
						if (is) {
							variance = call0(template_, PT_LC("getvariance"), "getVariance");
							if (UNEXPECTED(variance.isUndef())) return zv::Val();
						}
						if (UNEXPECTED(!varianceIs(variance.raw(), PT_TEMPLATE_TYPE_VARIANCE_INVARIANT, is))) return zv::Val();
						if (is) {
							constraints = pt_template_argument_constraints_with_send(constraints.raw(), parameterArgument, ancestorArgument, variance.raw());
							if (UNEXPECTED(constraints.isUndef())) return zv::Val();
							continue;
						}
						if (UNEXPECTED(!varianceIs(variance.raw(), PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT, is))) return zv::Val();
						if (is) {
							zval *covariant = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
							if (UNEXPECTED(covariant == NULL)) return zv::Val();
							constraints = pt_template_argument_constraints_with_send(constraints.raw(), parameterArgument, ancestorArgument, covariant);
							if (UNEXPECTED(constraints.isUndef())) return zv::Val();
							continue;
						}
						if (UNEXPECTED(!varianceIs(variance.raw(), PT_TEMPLATE_TYPE_VARIANCE_BIVARIANT, is))) return zv::Val();
						if (is) continue;
					}
				}
				constraints = observeLowerBound(std::move(constraints), parameterArgument, ancestorArgument);
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			}
			return constraints;
		}

		if (parameterReflectionCount > 0) return constraints;
		bool yes;
		if (UNEXPECTED(!trinaryYes(parameterType, PT_LC("isobject"), yes))) return zv::Val();
		if (yes) return constraints;
		if (UNEXPECTED(!trinaryYes(parameterType, PT_LC("isiterable"), yes))) return zv::Val();
		if (!yes) return constraints;
		if (UNEXPECTED(!trinaryYes(argumentType, PT_LC("isiterable"), yes))) return zv::Val();
		if (!yes) return constraints;

		zv::Val parameterKey = pt_type_op(Z_OBJ_P(parameterType), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(parameterKey.isUndef())) return zv::Val();
		zv::Val argumentKey = pt_type_op(Z_OBJ_P(argumentType), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(argumentKey.isUndef())) return zv::Val();
		constraints = observeLowerBound(std::move(constraints), parameterKey.raw(), argumentKey.raw());
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Val parameterValue = pt_type_op(Z_OBJ_P(parameterType), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(parameterValue.isUndef())) return zv::Val();
		zv::Val argumentValue = pt_type_op(Z_OBJ_P(argumentType), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(argumentValue.isUndef())) return zv::Val();
		return observeLowerBound(std::move(constraints), parameterValue.raw(), argumentValue.raw());
	}

	/* Mirrors isUninformativeSendTarget(); false = pending exception */
	[[nodiscard]] static bool isUninformativeSendTarget(zval *declaredArgument, bool &out)
	{
		if (UNEXPECTED(!isPlainMixed(declaredArgument, out))) return false;
		if (out) return true;
		zv::Val has = pt_type_op(Z_OBJ_P(declaredArgument), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
		if (UNEXPECTED(has.isUndef())) return false;
		out = zend_is_true(has.raw());
		return true;
	}

	/* Mirrors hasOnlyInferableTemplates(); false = pending exception */
	[[nodiscard]] static bool hasOnlyInferableTemplates(zval *type, bool &out)
	{
		zval *invariant = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_INVARIANT);
		if (UNEXPECTED(invariant == NULL)) return false;
		zv::Val references = pt_type_op(Z_OBJ_P(type), PT_OP_GET_REFERENCED_TEMPLATE_TYPES, 1, invariant);
		if (UNEXPECTED(references.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(references.raw()) != IS_ARRAY)) {
			zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(references.raw()));
			return false;
		}
		for (auto entry : zv::ArrRef(references.raw())) {
			zv::Val referencedType = call0(entry.value().deref().raw(), PT_LC("gettype"), "getType");
			if (UNEXPECTED(referencedType.isUndef())) return false;
			zv::Val isArgument = call0(referencedType.raw(), PT_LC("isargument"), "isArgument");
			if (UNEXPECTED(isArgument.isUndef())) return false;
			if (zend_is_true(isArgument.raw())) {
				out = false;
				return true;
			}
		}
		out = zend_hash_num_elements(Z_ARRVAL_P(references.raw())) > 0;
		return true;
	}

private:
	[[nodiscard]] static zv::Val undefinedKeyZero()
	{
		/* `$reflections[0]` of a one-element array keyed otherwise: the
		 * warning, then a member call on null */
		zend_error(E_WARNING, "Undefined array key 0");
		if (UNEXPECTED(EG(exception))) return zv::Val();
		zend_throw_error(NULL, "Call to a member function getName() on null");
		return zv::Val();
	}

	/* $reflection->getName() of a ClassReflection */
	static zv::Val reflectionName(zval *reflection)
	{
		if (UNEXPECTED(Z_TYPE_P(reflection) != IS_OBJECT)) return call0(reflection, PT_LC("getname"), "getName");
		return pt_class_reflection_get_name(Z_OBJ_P(reflection));
	}

	/* $reflection->isGeneric(); false = pending exception */
	[[nodiscard]] static bool reflectionIsGeneric(zval *reflection, bool &out)
	{
		if (UNEXPECTED(Z_TYPE_P(reflection) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isGeneric() on %s", zend_zval_value_name(reflection));
			return false;
		}
		return pt_class_reflection_is_generic(Z_OBJ_P(reflection), out);
	}

	/* $reflection->typeMapToList($reflection->get<Map>()) */
	static zv::Val reflectionTypeMapList(zval *reflection, const char *lcname, size_t len, const char *method)
	{
		zv::Val map = call0(reflection, lcname, len, method);
		if (UNEXPECTED(map.isUndef())) return zv::Val();
		return pt_class_reflection_type_map_to_list(Z_OBJ_P(reflection), map.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::TemplateArgumentObserver;

namespace {

/* static function (Type $type, callable $traverse) use (&$constraints): Type
 * — the constraints in the holder's first state slot */
void collectSitesBody(zval *constraints, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("TemplateArgumentObserver::collectSites() traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *type = &argv[0];
	if (Z_OBJCE_P(type) == pt_ce_unresolved_template_argument_type) {
		zv::Val next = pt_template_argument_constraints_with_site(constraints, type);
		if (UNEXPECTED(next.isUndef())) return;
		zv::Ref(constraints).assign(std::move(next));
		zv::Val initial = markerInitialType(type);
		if (UNEXPECTED(initial.isUndef())) return;
		if (Z_TYPE_P(initial.raw()) != IS_NULL) {
			zv::Val traversed = pt_type_call_callable(&argv[1], 1, initial.raw());
			if (UNEXPECTED(traversed.isUndef())) return;
		}
		ZVAL_COPY(return_value, type);
		return;
	}
	zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
	if (UNEXPECTED(traversed.isUndef())) return;
	traversed.intoReturnValue(return_value);
}

/* static function (Type $type, callable $traverse) use (&$contains): Type */
void containsMarkerBody(zval *contains, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("TemplateArgumentObserver::containsMarker() traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *type = &argv[0];
	if (Z_OBJCE_P(type) == pt_ce_unresolved_template_argument_type) {
		zval_ptr_dtor(contains);
		ZVAL_TRUE(contains);
	}
	if (Z_TYPE_P(contains) == IS_TRUE) {
		ZVAL_COPY(return_value, type);
		return;
	}
	zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
	if (UNEXPECTED(traversed.isUndef())) return;
	traversed.intoReturnValue(return_value);
}

/* containsMarker($type, true)'s traversal: a template argument marker only */
void containsTemplateArgumentMarkerBody(zval *contains, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("TemplateArgumentObserver::containsMarker() traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *type = &argv[0];
	if (Z_OBJCE_P(type) == pt_ce_unresolved_template_argument_type) {
		bool closureMarker;
		if (UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_signature(type, closureMarker))) return;
		if (!closureMarker) {
			zval_ptr_dtor(contains);
			ZVAL_TRUE(contains);
		}
	}
	if (Z_TYPE_P(contains) == IS_TRUE) {
		ZVAL_COPY(return_value, type);
		return;
	}
	zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
	if (UNEXPECTED(traversed.isUndef())) return;
	traversed.intoReturnValue(return_value);
}

/* containsClosureSignatureMarker()'s traversal */
void containsClosureSignatureMarkerBody(zval *contains, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("TemplateArgumentObserver::containsClosureSignatureMarker() traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *type = &argv[0];
	if (Z_OBJCE_P(type) == pt_ce_unresolved_template_argument_type) {
		bool closureMarker;
		if (UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_signature(type, closureMarker))) return;
		if (closureMarker) {
			zval_ptr_dtor(contains);
			ZVAL_TRUE(contains);
		}
	}
	if (Z_TYPE_P(contains) == IS_TRUE) {
		ZVAL_COPY(return_value, type);
		return;
	}
	zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
	if (UNEXPECTED(traversed.isUndef())) return;
	traversed.intoReturnValue(return_value);
}

/* escapeClosures()'s traversal: static function (Type $type, callable
 * $traverse) use (&$constraints): Type */
void escapeClosuresBody(zval *constraints, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("TemplateArgumentObserver::escapeClosures() traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *type = &argv[0];
	if (Z_OBJCE_P(type) == pt_ce_unresolved_template_argument_type) {
		bool closureMarker;
		if (UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_signature(type, closureMarker))) return;
		bool returnMarker = false;
		if (closureMarker && UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_return(type, returnMarker))) return;
		if (closureMarker && !returnMarker) {
			zv::Val next = pt_template_argument_constraints_with_unconstraining_send(constraints, type);
			if (UNEXPECTED(next.isUndef())) return;
			zv::Ref(constraints).assign(std::move(next));
		}
		zv::Val initial = markerInitialType(type);
		if (UNEXPECTED(initial.isUndef())) return;
		if (Z_TYPE_P(initial.raw()) != IS_NULL) {
			zv::Val traversed = pt_type_call_callable(&argv[1], 1, initial.raw());
			if (UNEXPECTED(traversed.isUndef())) return;
		}
		ZVAL_COPY(return_value, type);
		return;
	}
	zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
	if (UNEXPECTED(traversed.isUndef())) return;
	traversed.intoReturnValue(return_value);
}

/* function (Type $type, callable $traverse) use ($site, $templates, &$constraints): Type
 * — captures: $this, $site, $templates, the reference */
void replaceInferableTemplatesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("TemplateArgumentObserver::replaceInferableTemplates() traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *self = &captures[0];
	zval *site = &captures[1];
	zval *templates = &captures[2];
	zval *reference = &captures[3];
	zval *type = &argv[0];

	bool plainUnion;
	if (UNEXPECTED(!isPlainUnion(type, plainUnion))) return;
	if (plainUnion) {
		zv::Val replaced = TemplateArgumentObserver::replaceInferableTemplates(self, type, site, templates, reference);
		if (UNEXPECTED(replaced.isUndef())) return;
		replaced.intoReturnValue(return_value);
		return;
	}
	bool isTemplate;
	if (UNEXPECTED(!isTemplateType(type, isTemplate))) return;
	bool traverse = !isTemplate;
	if (!traverse) {
		zv::Val isArgument = pt_type_call(Z_OBJ_P(type), PT_LC("isargument"), 0, NULL);
		if (UNEXPECTED(isArgument.isUndef())) return;
		traverse = zend_is_true(isArgument.raw());
	}
	if (traverse) {
		zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
		return;
	}

	zv::Val name = pt_type_call(Z_OBJ_P(type), PT_LC("getname"), 0, NULL);
	if (UNEXPECTED(name.isUndef())) return;
	zv::Val template_ = Z_TYPE_P(name.raw()) == IS_STRING
		? pt_template_type_map_get_type(templates, Z_STR_P(name.raw()))
		: pt_type_call(Z_OBJ_P(templates), PT_LC("gettype"), 1, name.raw());
	if (UNEXPECTED(template_.isUndef())) return;
	bool templateIsTemplate;
	if (UNEXPECTED(!isTemplateType(template_.raw(), templateIsTemplate))) return;
	bool sameScope = false;
	if (templateIsTemplate) {
		zv::Val templateScope = pt_type_call(Z_OBJ_P(template_.raw()), PT_LC("getscope"), 0, NULL);
		if (UNEXPECTED(templateScope.isUndef())) return;
		zv::Val typeScope = pt_type_call(Z_OBJ_P(type), PT_LC("getscope"), 0, NULL);
		if (UNEXPECTED(typeScope.isUndef())) return;
		if (UNEXPECTED(Z_TYPE_P(templateScope.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(templateScope.raw()));
			return;
		}
		if (UNEXPECTED(!pt_template_type_scope_equals(templateScope.raw(), typeScope.raw(), sameScope))) return;
	}
	if (!templateIsTemplate || !sameScope) {
		ZVAL_COPY(return_value, type);
		return;
	}

	zval marker;
	if (UNEXPECTED(!pt_unresolved_template_argument_type_new(&marker, site, type, NULL))) return;
	zv::Val markerHold = zv::Val::adopt(marker);
	zval *constraints = Z_REFVAL_P(reference);
	zv::Val next = pt_template_argument_constraints_with_site(constraints, markerHold.raw());
	if (UNEXPECTED(next.isUndef())) return;
	zv::Ref(constraints).assign(std::move(next));
	markerHold.intoReturnValue(return_value);
}

} // namespace

/* {{{ direct entries (support.h): the native body for the native service,
 * the method otherwise */

namespace {

[[nodiscard]] bool observerReceiver(zval *observer, const char *method)
{
	if (EXPECTED(Z_TYPE_P(observer) == IS_OBJECT)) return true;
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(observer));
	return false;
}

} // namespace

zv::Val pt_template_argument_observer_collect_sites(zval *observer, zval *type)
{
	if (UNEXPECTED(!observerReceiver(observer, "collectSites"))) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(observer) == pt_ce_template_argument_observer && Z_TYPE_P(type) == IS_OBJECT)) return TemplateArgumentObserver::collectSites(type);
	return pt_type_call(Z_OBJ_P(observer), PT_LC("collectsites"), 1, type);
}

zv::Val pt_template_argument_observer_collect_send(zval *observer, zval *declared, zval *actual)
{
	if (UNEXPECTED(!observerReceiver(observer, "collectSend"))) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(observer) == pt_ce_template_argument_observer && Z_TYPE_P(declared) == IS_OBJECT && Z_TYPE_P(actual) == IS_OBJECT)) return TemplateArgumentObserver::collectSend(declared, actual);
	zv::Args argv{declared, actual};
	return pt_type_call(Z_OBJ_P(observer), PT_LC("collectsend"), 2, argv);
}

zv::Val pt_template_argument_observer_collect_argument(zval *observer, zval *parameterType, zval *argumentType, bool isPure)
{
	if (UNEXPECTED(!observerReceiver(observer, "collectArgument"))) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(observer) == pt_ce_template_argument_observer && Z_TYPE_P(parameterType) == IS_OBJECT && Z_TYPE_P(argumentType) == IS_OBJECT)) return TemplateArgumentObserver::collectArgument(parameterType, argumentType, isPure);
	zv::Args argv{parameterType, argumentType, isPure};
	return pt_type_call(Z_OBJ_P(observer), PT_LC("collectargument"), 3, argv);
}

zv::Val pt_template_argument_observer_collect_call(zval *observer, zval *site, zval *acceptor, zval *argumentTypes, zval *classTemplates)
{
	if (UNEXPECTED(!observerReceiver(observer, "collectCall"))) return zv::Val();
	zval null;
	ZVAL_NULL(&null);
	if (classTemplates == NULL) classTemplates = &null;
	if (EXPECTED(Z_OBJCE_P(observer) == pt_ce_template_argument_observer && Z_TYPE_P(site) == IS_OBJECT && Z_TYPE_P(acceptor) == IS_OBJECT && Z_TYPE_P(argumentTypes) == IS_ARRAY)) {
		return TemplateArgumentObserver::collectCall(observer, site, acceptor, argumentTypes, classTemplates);
	}
	zv::Args argv{site, acceptor, argumentTypes, classTemplates};
	return pt_type_call(Z_OBJ_P(observer), PT_LC("collectcall"), 4, argv);
}

zv::Val pt_template_argument_observer_collect_closure_argument(zval *observer, zval *parameterType, zval *argumentType)
{
	if (UNEXPECTED(!observerReceiver(observer, "collectClosureArgument"))) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(observer) == pt_ce_template_argument_observer && Z_TYPE_P(parameterType) == IS_OBJECT && Z_TYPE_P(argumentType) == IS_OBJECT)) return TemplateArgumentObserver::collectClosureArgument(parameterType, argumentType);
	zv::Args argv{parameterType, argumentType};
	return pt_type_call(Z_OBJ_P(observer), PT_LC("collectclosureargument"), 2, argv);
}

zv::Val pt_template_argument_observer_collect_closure_arguments(zval *observer, zval *acceptor, zval *argumentTypes, bool isPure)
{
	if (UNEXPECTED(!observerReceiver(observer, "collectClosureArguments"))) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(observer) == pt_ce_template_argument_observer && Z_TYPE_P(acceptor) == IS_OBJECT && Z_TYPE_P(argumentTypes) == IS_ARRAY)) return TemplateArgumentObserver::collectClosureArguments(acceptor, argumentTypes, isPure);
	zv::Args argv{acceptor, argumentTypes, isPure};
	return pt_type_call(Z_OBJ_P(observer), PT_LC("collectclosurearguments"), 3, argv);
}

zv::Val pt_template_argument_observer_collect_escape(zval *observer, zval *type)
{
	if (UNEXPECTED(!observerReceiver(observer, "collectEscape"))) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(observer) == pt_ce_template_argument_observer && Z_TYPE_P(type) == IS_OBJECT)) return TemplateArgumentObserver::collectEscape(type);
	return pt_type_call(Z_OBJ_P(observer), PT_LC("collectescape"), 1, type);
}

bool pt_template_argument_observer_carries_closure_signature_markers(zval *observer, zval *types, bool &out)
{
	if (UNEXPECTED(!observerReceiver(observer, "carriesClosureSignatureMarkers"))) return false;
	if (EXPECTED(Z_OBJCE_P(observer) == pt_ce_template_argument_observer && Z_TYPE_P(types) == IS_ARRAY)) return TemplateArgumentObserver::carriesClosureSignatureMarkers(types, out);
	zv::Val result = pt_type_call(Z_OBJ_P(observer), PT_LC("carriesclosuresignaturemarkers"), 1, types);
	if (UNEXPECTED(result.isUndef())) return false;
	out = Z_TYPE_P(result.raw()) == IS_TRUE;
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_template_argument_observer)
{
	reg::Class cls("PHPStan\\Analyser\\Generics\\TemplateArgumentObserver");
	ptdecl::TemplateArgumentObserver::declareClass(cls);
	ptdecl::TemplateArgumentObserver::declareProperties(cls);

	cls.method(sigs::collectSites, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(type, pt_class(PT_CLASS_TYPE))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TemplateArgumentObserver::collectSites(type));
	});

	cls.method(sigs::collectSend, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *declared, *actual;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(declared, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(actual, pt_class(PT_CLASS_TYPE))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TemplateArgumentObserver::collectSend(declared, actual));
	});

	cls.method(sigs::collectArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parameterType, *argumentType;
		bool isPure = false;
		ZEND_PARSE_PARAMETERS_START(2, 3)
			Z_PARAM_OBJECT_OF_CLASS(parameterType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(argumentType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(isPure)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TemplateArgumentObserver::collectArgument(parameterType, argumentType, isPure));
	});

	cls.method(sigs::collectCall, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *site, *acceptor, *argumentTypes, *classTemplates = NULL;
		ZEND_PARSE_PARAMETERS_START(3, 4)
			Z_PARAM_OBJECT_OF_CLASS(site, pt_class(PT_CLASS_EXPR))
			Z_PARAM_OBJECT_OF_CLASS(acceptor, pt_class(PT_CLASS_PARAMETERS_ACCEPTOR))
			Z_PARAM_ARRAY(argumentTypes)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(classTemplates, pt_ce_template_type_map)
		ZEND_PARSE_PARAMETERS_END();
		zval null;
		ZVAL_NULL(&null);
		PT_RETURN_VAL(TemplateArgumentObserver::collectCall(ZEND_THIS, site, acceptor, argumentTypes, classTemplates != NULL ? classTemplates : &null));
	});

	cls.method(sigs::collectClosureArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parameterType, *argumentType;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(parameterType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(argumentType, pt_class(PT_CLASS_TYPE))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TemplateArgumentObserver::collectClosureArgument(parameterType, argumentType));
	});

	cls.method(sigs::collectClosureArguments, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptor, *argumentTypes;
		bool isPure;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT_OF_CLASS(acceptor, pt_class(PT_CLASS_PARAMETERS_ACCEPTOR))
			Z_PARAM_ARRAY(argumentTypes)
			Z_PARAM_BOOL(isPure)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TemplateArgumentObserver::collectClosureArguments(acceptor, argumentTypes, isPure));
	});

	cls.method(sigs::collectEscape, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(type, pt_class(PT_CLASS_TYPE))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TemplateArgumentObserver::collectEscape(type));
	});

	cls.method(sigs::carriesClosureSignatureMarkers, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(types)
		ZEND_PARSE_PARAMETERS_END();
		bool out;
		if (UNEXPECTED(!TemplateArgumentObserver::carriesClosureSignatureMarkers(types, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.shadow(&pt_ce_template_argument_observer);
}

/* }}} */
