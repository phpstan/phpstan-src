/*
 * PHPStanTurbo\ResolvedFunctionVariantWithOriginal — native implementation
 * of PHPStan\Reflection\ResolvedFunctionVariantWithOriginal.
 *
 * The variant every call site's selected acceptor is: the original acceptor
 * with its template types resolved against the call's inferred template map
 * and call-site variances, the parameters and return types memoized in the
 * twin's own slots. The twin's closures — the parameter mapping of
 * getParameters(), the two TypeTraverser callbacks of
 * resolveResolvableTemplateTypes() and the one of
 * resolveConditionalTypesForParameter() — are native closures capturing what
 * the twin's capture ($this included), run by the native TypeTraverser
 * without a frame. The original acceptor and its parameters are asked through
 * pt_parameters_acceptor_call() / pt_parameter_reflection_call(), the
 * template types, variance maps and conditional types through one cached
 * method site per method, the getReturnTypeWithUnresolvedTemplateArguments()
 * memo keeps the twin's WeakReferences.
 */

#include "support.h"
#include "generated/ResolvedFunctionVariantWithOriginal.h"

namespace slots = ptdecl::ResolvedFunctionVariantWithOriginal::slot;
namespace sigs = ptdecl::ResolvedFunctionVariantWithOriginal::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AcceptorValues.h"
#include "ParameterValues.h"

#include "zend_weakrefs.h"

zend_class_entry *pt_ce_resolved_function_variant_with_original = NULL;

namespace {

/* {{{ the collaborators (one site each) */

pt_method_site pt_rfv_is_argument_site;
pt_method_site pt_rfv_template_get_name_site;
pt_method_site pt_rfv_template_get_scope_site;
pt_method_site pt_rfv_template_get_bound_site;
pt_method_site pt_rfv_scope_get_function_name_site;
pt_method_site pt_rfv_get_variance_site;
pt_method_site pt_rfv_get_parameter_name_site;
pt_method_site pt_rfv_weakref_get_site;

/* $templateType->isArgument(); false = pending exception */
[[nodiscard]] bool templateIsArgument(zval *type, bool &out)
{
	zv::Val result = pt_call_method_cached(pt_rfv_is_argument_site, Z_OBJ_P(type), PT_LC("isargument"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* $templateType->getName() */
zv::Val templateName(zval *type)
{
	return pt_call_method_cached(pt_rfv_template_get_name_site, Z_OBJ_P(type), PT_LC("getname"), 0, NULL);
}

/* $templateType->getScope()->getFunctionName() !== null; false = pending exception */
[[nodiscard]] bool templateHasFunctionScope(zval *type, bool &out)
{
	zv::Val scope = pt_call_method_cached(pt_rfv_template_get_scope_site, Z_OBJ_P(type), PT_LC("getscope"), 0, NULL);
	if (UNEXPECTED(scope.isUndef())) return false;
	zv::Val functionName = pt_call_method_cached(pt_rfv_scope_get_function_name_site, Z_OBJ_P(scope.raw()), PT_LC("getfunctionname"), 0, NULL);
	if (UNEXPECTED(functionName.isUndef())) return false;
	out = !functionName.isNull();
	return true;
}

/* $templateType->getBound() */
zv::Val templateBound(zval *type)
{
	return pt_call_method_cached(pt_rfv_template_get_bound_site, Z_OBJ_P(type), PT_LC("getbound"), 0, NULL);
}

/* $callSiteVarianceMap->getVariance($name) */
zv::Val varianceMapGetVariance(zval *map, zval *name)
{
	return pt_call_method_cached(pt_rfv_get_variance_site, Z_OBJ_P(map), PT_LC("getvariance"), 1, name);
}

/* $conditionalTypeForParameter->getParameterName() */
zv::Val conditionalParameterName(zval *type)
{
	return pt_call_method_cached(pt_rfv_get_parameter_name_site, Z_OBJ_P(type), PT_LC("getparametername"), 0, NULL);
}

/* the PT_TEMPLATE_TYPE_VARIANCE_* value of a variance; -1 = pending exception */
zend_long varianceOf(zval *variance)
{
	zend_long value;
	if (UNEXPECTED(!pt_template_type_variance_value_of(variance, value))) return -1;
	return value;
}

/* WeakReference::create($object) */
zv::Val weakReferenceCreate(zval *object)
{
	static zend_function *create = NULL;
	if (UNEXPECTED(create == NULL)) {
		create = (zend_function *) zend_hash_str_find_ptr(&zend_ce_weakref->function_table, ZEND_STRL("create"));
		ZEND_ASSERT(create != NULL);
	}
	zval result;
	ZVAL_UNDEF(&result);
	zend_call_known_function(create, NULL, zend_ce_weakref, &result, 1, object, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&result);
		return zv::Val();
	}
	return zv::Val::adopt(result);
}

/* $weakReference->get() === $object; false = pending exception */
[[nodiscard]] bool weakReferenceIs(zval *weakReference, zval *object, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(weakReference) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function get() on %s", zend_zval_value_name(weakReference));
		return false;
	}
	zv::Val referent = pt_call_method_cached(pt_rfv_weakref_get_site, Z_OBJ_P(weakReference), PT_LC("get"), 0, NULL);
	if (UNEXPECTED(referent.isUndef())) return false;
	out = Z_TYPE_P(referent.raw()) == IS_OBJECT && Z_OBJ_P(referent.raw()) == Z_OBJ_P(object);
	return true;
}

/* the twin's closure names, for the engine's messages */
#define PT_RFV_CLASS "PHPStan\\Reflection\\ResolvedFunctionVariantWithOriginal"
#if PHP_VERSION_ID >= 80400
#define PT_RFV_CLOSURE(method, line) PT_RFV_CLASS "::{closure:" PT_RFV_CLASS "::" method "():" line "}"
#else
/* PHP 8.3 names a closure by its namespace alone */
#define PT_RFV_CLOSURE(method, line) PT_RFV_CLASS "::PHPStan\\Reflection\\{closure}"
#endif
#define PT_RFV_PARAMETERS_CLOSURE PT_RFV_CLOSURE("getParameters", "89")

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\ResolvedFunctionVariantWithOriginal; UNDEF =
 * pending exception. */
class ResolvedFunctionVariantWithOriginal
{
public:
	explicit ResolvedFunctionVariantWithOriginal(zend_object *self) : self(self) {}

	/* the promoted slots */
	void construct(zval *parametersAcceptor, zval *resolvedTemplateTypeMap, zval *callSiteVarianceMap, zval *passedArgs) const
	{
		pt_write_slot(self, slots::parametersAcceptor, parametersAcceptor);
		pt_write_slot(self, slots::resolvedTemplateTypeMap, resolvedTemplateTypeMap);
		pt_write_slot(self, slots::callSiteVarianceMap, callSiteVarianceMap);
		pt_write_slot(self, slots::passedArgs, passedArgs);
	}

	static zv::Val create(zval *parametersAcceptor, zval *resolvedTemplateTypeMap, zval *callSiteVarianceMap, zval *passedArgs)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_resolved_function_variant_with_original) != SUCCESS)) return zv::Val();
		ResolvedFunctionVariantWithOriginal(Z_OBJ(object)).construct(parametersAcceptor, resolvedTemplateTypeMap, callSiteVarianceMap, passedArgs);
		return zv::Val::adopt(object);
	}

	zv::Val getOriginalParametersAcceptor() const { return copyOf(slots::parametersAcceptor, "parametersAcceptor"); }
	zv::Val getTemplateTypeMap() const { return delegate(PT_PA_GET_TEMPLATE_TYPE_MAP); }
	zv::Val getResolvedTemplateTypeMap() const { return copyOf(slots::resolvedTemplateTypeMap, "resolvedTemplateTypeMap"); }
	zv::Val getCallSiteVarianceMap() const { return copyOf(slots::callSiteVarianceMap, "callSiteVarianceMap"); }

	/* Mirrors getParameters(): memoized in $parameters. */
	zv::Val getParameters() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::parameters);
		if (EXPECTED(Z_TYPE_P(memo) == IS_ARRAY)) return zv::Val::copyOf(zv::Ref(memo));

		zv::Val originalParameters = delegate(PT_PA_GET_PARAMETERS);
		if (UNEXPECTED(originalParameters.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(originalParameters.raw()) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(originalParameters.raw()));
			return zv::Val();
		}
		/* array_map(fn, $parameters): the keys kept */
		HashTable *table = Z_ARRVAL_P(originalParameters.raw());
		zv::Arr parameters = zv::Arr::create(zend_hash_num_elements(table));
		for (zv::ArrayEntry entry : zv::TableRef(table)) {
			zv::Val parameter = resolveParameter(entry.value().deref().raw());
			if (UNEXPECTED(parameter.isUndef())) return zv::Val();
			zval value = parameter.take();
			if (entry.hasStringKey()) {
				zend_hash_update(parameters.table(), entry.stringKey(), &value);
			} else {
				zend_hash_index_update(parameters.table(), entry.indexKey(), &value);
			}
		}
		zv::Val result(std::move(parameters));
		pt_write_slot(self, slots::parameters, result.raw());
		return result;
	}

	zv::Val isVariadic() const { return delegate(PT_PA_IS_VARIADIC); }

	/* $this->returnTypeWithUnresolvableTemplateTypes ??=
	 * $this->resolveConditionalTypesForParameter($this->resolveResolvableTemplateTypes(
	 * $this->parametersAcceptor->getReturnType(), TemplateTypeVariance::createCovariant())) */
	zv::Val getReturnTypeWithUnresolvableTemplateTypes() const
	{
		return unresolvableMemo(slots::returnTypeWithUnresolvableTemplateTypes, PT_PA_GET_RETURN_TYPE);
	}

	/* the same for the PHPDoc return type (private) */
	zv::Val getPhpDocReturnTypeWithUnresolvableTemplateTypes() const
	{
		return unresolvableMemo(slots::phpDocReturnTypeWithUnresolvableTemplateTypes, PT_PA_GET_PHPDOC_RETURN_TYPE);
	}

	/* Mirrors getReturnType(): memoized in $returnType. */
	zv::Val getReturnType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::returnType);
		if (EXPECTED(Z_TYPE_P(memo) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(memo));
		zv::Val unresolvable = getReturnTypeWithUnresolvableTemplateTypes();
		if (UNEXPECTED(unresolvable.isUndef())) return zv::Val();
		zv::Val type = resolveTemplateTypesAndLateResolvable(unresolvable.raw());
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		pt_write_slot(self, slots::returnType, type.raw());
		return type;
	}

	/* Mirrors getReturnTypeWithUnresolvedTemplateArguments(). */
	zv::Val getReturnTypeWithUnresolvedTemplateArguments(zval *site, zval *frame, bool allowUnresolved) const
	{
		zval *hasMemo = OBJ_PROP_NUM(self, slots::hasTemplateOrLateResolvableReturnType);
		bool hasTemplate;
		if (EXPECTED(Z_TYPE_P(hasMemo) == IS_TRUE || Z_TYPE_P(hasMemo) == IS_FALSE)) {
			hasTemplate = Z_TYPE_P(hasMemo) == IS_TRUE;
		} else {
			zv::Val returnType = delegate(PT_PA_GET_RETURN_TYPE);
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
			zv::Val has = pt_type_op(Z_OBJ_P(returnType.raw()), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
			if (UNEXPECTED(has.isUndef())) return zv::Val();
			hasTemplate = zend_is_true(has.raw());
			zval value;
			ZVAL_BOOL(&value, hasTemplate);
			pt_write_slot(self, slots::hasTemplateOrLateResolvableReturnType, &value);
		}
		if (!hasTemplate) return getReturnType();

		zval *cached = OBJ_PROP_NUM(self, slots::returnTypeWithUnresolvedTemplateArguments);
		if (Z_TYPE_P(cached) == IS_ARRAY) {
			HashTable *entries = Z_ARRVAL_P(cached);
			zval *cachedSite = zend_hash_index_find(entries, 0);
			zval *cachedFrame = zend_hash_index_find(entries, 1);
			zval *cachedAllowUnresolved = zend_hash_index_find(entries, 2);
			zval *cachedType = zend_hash_index_find(entries, 3);
			if (EXPECTED(cachedSite != NULL && cachedFrame != NULL && cachedAllowUnresolved != NULL && cachedType != NULL)) {
				bool same;
				if (UNEXPECTED(!weakReferenceIs(cachedSite, site, same))) return zv::Val();
				if (same && UNEXPECTED(!weakReferenceIs(cachedFrame, frame, same))) return zv::Val();
				if (same && Z_TYPE_P(cachedAllowUnresolved) == (allowUnresolved ? IS_TRUE : IS_FALSE)) return zv::Val::copyOf(zv::Ref(cachedType));
			}
		}

		zv::Val returnType = delegate(PT_PA_GET_RETURN_TYPE);
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zv::Val narrowed = narrowTemplateTypesInConditionalTypesForParameter(returnType.raw());
		if (UNEXPECTED(narrowed.isUndef())) return zv::Val();
		zval *covariant = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
		if (UNEXPECTED(covariant == NULL)) return zv::Val();
		zv::Val resolvable = resolveResolvableTemplateTypes(narrowed.raw(), covariant, site, frame, allowUnresolved);
		if (UNEXPECTED(resolvable.isUndef())) return zv::Val();
		zv::Val conditional = resolveConditionalTypesForParameter(resolvable.raw());
		if (UNEXPECTED(conditional.isUndef())) return zv::Val();
		zv::Val type = resolveTemplateTypesAndLateResolvable(conditional.raw());
		if (UNEXPECTED(type.isUndef())) return zv::Val();

		zv::Val siteReference = weakReferenceCreate(site);
		if (UNEXPECTED(siteReference.isUndef())) return zv::Val();
		zv::Val frameReference = weakReferenceCreate(frame);
		if (UNEXPECTED(frameReference.isUndef())) return zv::Val();
		zv::Arr memo = zv::Arr::create(4);
		memo.push(std::move(siteReference));
		memo.push(std::move(frameReference));
		memo.push(zv::Val::boolean(allowUnresolved));
		memo.push(zv::Ref(type.raw()));
		zv::Val memoValue(std::move(memo));
		pt_write_slot(self, slots::returnTypeWithUnresolvedTemplateArguments, memoValue.raw());
		return type;
	}

	/* Mirrors getPhpDocReturnType(): memoized in $phpDocReturnType. */
	zv::Val getPhpDocReturnType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::phpDocReturnType);
		if (EXPECTED(Z_TYPE_P(memo) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(memo));
		zv::Val unresolvable = getPhpDocReturnTypeWithUnresolvableTemplateTypes();
		if (UNEXPECTED(unresolvable.isUndef())) return zv::Val();
		zv::Val type = resolveTemplateTypesAndLateResolvable(unresolvable.raw());
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		pt_write_slot(self, slots::phpDocReturnType, type.raw());
		return type;
	}

	zv::Val getNativeReturnType() const { return delegate(PT_PA_GET_NATIVE_RETURN_TYPE); }

	/* Mirrors resolveResolvableTemplateTypes() (private); $site / $frame NULL
	 * for null. */
	zv::Val resolveResolvableTemplateTypes(zval *type, zval *positionVariance, zval *site, zval *frame, bool allowUnresolved) const
	{
		zv::Val references = pt_type_op(Z_OBJ_P(type), PT_OP_GET_REFERENCED_TEMPLATE_TYPES, 1, positionVariance);
		if (UNEXPECTED(references.isUndef())) return zv::Val();

		zval null = {};
		ZVAL_NULL(&null);
		zv::Val objectCallback = pt_native_closure(&objectCallbackBody, self, references.raw(), site != NULL ? site : &null, frame != NULL ? frame : &null, allowUnresolved);
		zv::Val callback = pt_native_closure(&typeCallbackBody, self, references.raw(), objectCallback.raw());
		zval mapped;
		if (UNEXPECTED(!pt_type_traverser_map(&mapped, type, callback.raw()))) return zv::Val();
		return zv::Val::adopt(mapped);
	}

	/* Mirrors unresolvedOrResolvedTemplateArgument() (private). */
	static zv::Val unresolvedOrResolvedTemplateArgument(zval *templateType, zval *inferred, zval *site, zval *frame, bool allowUnresolved)
	{
		if (allowUnresolved) {
			bool observing;
			if (UNEXPECTED(!pt_template_argument_frame_is_observing(frame, observing))) return zv::Val();
			if (observing) {
				if (Z_TYPE_P(inferred) == IS_OBJECT && instanceof_function(Z_OBJCE_P(inferred), pt_ce_unresolved_template_argument_type)) return zv::Val::copyOf(zv::Ref(inferred));
				zv::Val unresolved;
				if (UNEXPECTED(!pt_unresolved_template_argument_type_new(unresolved.raw(), site, templateType, inferred))) return zv::Val();
				return unresolved;
			}
		}
		zv::Val name = templateName(templateType);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(name.raw()) != IS_STRING)) {
			zend_type_error("phpstan_turbo: getName() must return string");
			return zv::Val();
		}
		zv::Val resolved = pt_template_argument_frame_resolve(frame, site, Z_STR_P(name.raw()));
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		if (!resolved.isNull()) return resolved;
		return zv::Val::copyOf(zv::Ref(inferred));
	}

	/* Mirrors resolveConditionalTypesForParameter() (private):
	 * ConditionalTypeForParameter::resolveInType($type,
	 * fn (string $parameterName): ?Type => $this->passedArgs[$parameterName] ?? null) */
	zv::Val resolveConditionalTypesForParameter(zval *type) const
	{
		zval *passedArgs = slot(slots::passedArgs, "passedArgs");
		if (UNEXPECTED(passedArgs == NULL)) return zv::Val();
		return pt_conditional_type_for_parameter_resolve_in_type_with_args(type, passedArgs);
	}

	/* referencesTemplateType() for the registration glue; false = pending exception */
	[[nodiscard]] static bool typeReferencesTemplateType(zval *type, zval *templateType, bool &out)
	{
		return referencesTemplateType(type, templateType, out);
	}

	/* $this->passedArgs !== [] */
	zv::Val hasBoundArgs() const
	{
		zval *passedArgs = slot(slots::passedArgs, "passedArgs");
		if (UNEXPECTED(passedArgs == NULL)) return zv::Val();
		return zv::Val::boolean(Z_TYPE_P(passedArgs) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(passedArgs)) != 0);
	}

	/* TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes(
	 * $this->resolveConditionalTypesForParameter($type), $this->resolvedTemplateTypeMap,
	 * $this->callSiteVarianceMap, TemplateTypeVariance::createCovariant()), false) */
	zv::Val resolveConditionalTypes(zval *type) const
	{
		zv::Val conditional = resolveConditionalTypesForParameter(type);
		if (UNEXPECTED(conditional.isUndef())) return zv::Val();
		return resolveTemplateTypesAndLateResolvable(conditional.raw());
	}

	/* Mirrors narrowTemplateTypesInConditionalTypesForParameter() (private):
	 * `($param is X ? A : B)` narrows the template type the parameter alone
	 * binds along with it, before the template types resolve. */
	zv::Val narrowTemplateTypesInConditionalTypesForParameter(zval *type) const
	{
		zv::Val has = pt_type_op(Z_OBJ_P(type), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
		if (UNEXPECTED(has.isUndef())) return zv::Val();
		if (!zend_is_true(has.raw())) return zv::Val::copyOf(zv::Ref(type));
		zv::Val callback = pt_native_closure(&narrowCallbackBody, self);
		zval mapped;
		if (UNEXPECTED(!pt_type_traverser_map(&mapped, type, callback.raw()))) return zv::Val();
		return zv::Val::adopt(mapped);
	}

	/* Mirrors getTemplateTypeBoundOnlyByParameter() (private): the template
	 * type of the function the parameter is declared as, provided no other
	 * parameter references it; null otherwise. UNDEF = pending exception */
	zv::Val getTemplateTypeBoundOnlyByParameter(zval *parameterName) const
	{
		zv::Val parameters = delegate(PT_PA_GET_PARAMETERS);
		if (UNEXPECTED(parameters.isUndef())) return zv::Val();
		zv::Val templateType;
		if (Z_TYPE_P(parameters.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry entry : zv::ArrRef(parameters.raw())) {
				zval *parameter = entry.value().deref().raw();
				bool same;
				if (UNEXPECTED(!parameterIsNamed(parameter, parameterName, same))) return zv::Val();
				if (!same) continue;
				bool isVariadic;
				if (UNEXPECTED(!pt_parameter_reflection_bool(parameter, PT_PR_IS_VARIADIC, isVariadic))) return zv::Val();
				if (isVariadic) return zv::Val::null();

				zv::Val type = pt_parameter_reflection_call(parameter, PT_PR_GET_TYPE);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				bool isTemplate;
				if (UNEXPECTED(!pt_type_instanceof(type.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
				if (!isTemplate) return zv::Val::null();
				bool isNarrowedSubject;
				if (UNEXPECTED(!pt_type_instanceof(type.raw(), PT_CLASS_NARROWED_SUBJECT_TYPE, isNarrowedSubject))) return zv::Val();
				if (isNarrowedSubject) return zv::Val::null();
				bool functionScope;
				if (UNEXPECTED(!templateHasFunctionScope(type.raw(), functionScope))) return zv::Val();
				if (!functionScope) return zv::Val::null();

				templateType = std::move(type);
				break;
			}
		}
		if (templateType.isUndef()) return zv::Val::null();

		zv::Val otherParameters = delegate(PT_PA_GET_PARAMETERS);
		if (UNEXPECTED(otherParameters.isUndef())) return zv::Val();
		if (Z_TYPE_P(otherParameters.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry entry : zv::ArrRef(otherParameters.raw())) {
				zval *parameter = entry.value().deref().raw();
				bool same;
				if (UNEXPECTED(!parameterIsNamed(parameter, parameterName, same))) return zv::Val();
				if (same) continue;
				zv::Val type = pt_parameter_reflection_call(parameter, PT_PR_GET_TYPE);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				bool references;
				if (UNEXPECTED(!referencesTemplateType(type.raw(), templateType.raw(), references))) return zv::Val();
				if (references) return zv::Val::null();
			}
		}

		return templateType;
	}

private:
	zend_object *self;

	zval *slot(uint32_t index, const char *propertyName) const
	{
		return pt_typed_slot(self, index, pt_ce_resolved_function_variant_with_original, propertyName);
	}

	zv::Val copyOf(uint32_t index, const char *propertyName) const
	{
		zval *value = slot(index, propertyName);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}

	/* $this->parametersAcceptor-><member>() */
	zv::Val delegate(pt_parameters_acceptor_member member) const
	{
		zval *acceptor = slot(slots::parametersAcceptor, "parametersAcceptor");
		if (UNEXPECTED(acceptor == NULL)) return zv::Val();
		return pt_parameters_acceptor_call(acceptor, member);
	}

	/* $this->memo ??= $this->resolveConditionalTypesForParameter(
	 * $this->resolveResolvableTemplateTypes($this->narrowTemplateTypesInConditionalTypesForParameter(
	 * $this->parametersAcceptor->getX()), TemplateTypeVariance::createCovariant())) */
	zv::Val unresolvableMemo(uint32_t memoSlot, pt_parameters_acceptor_member member) const
	{
		zval *memo = OBJ_PROP_NUM(self, memoSlot);
		if (EXPECTED(Z_TYPE_P(memo) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(memo));
		zv::Val type = delegate(member);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zv::Val narrowed = narrowTemplateTypesInConditionalTypesForParameter(type.raw());
		if (UNEXPECTED(narrowed.isUndef())) return zv::Val();
		zval *covariant = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
		if (UNEXPECTED(covariant == NULL)) return zv::Val();
		zv::Val resolvable = resolveResolvableTemplateTypes(narrowed.raw(), covariant, NULL, NULL, true);
		if (UNEXPECTED(resolvable.isUndef())) return zv::Val();
		zv::Val conditional = resolveConditionalTypesForParameter(resolvable.raw());
		if (UNEXPECTED(conditional.isUndef())) return zv::Val();
		pt_write_slot(self, memoSlot, conditional.raw());
		return conditional;
	}

	/* TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes($type,
	 * $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap,
	 * TemplateTypeVariance::create<variance>()), false) */
	zv::Val resolveTemplateTypesAndLateResolvable(zval *type, zend_long variance = PT_TEMPLATE_TYPE_VARIANCE_COVARIANT) const
	{
		zval *resolvedTemplateTypeMap = slot(slots::resolvedTemplateTypeMap, "resolvedTemplateTypeMap");
		if (UNEXPECTED(resolvedTemplateTypeMap == NULL)) return zv::Val();
		zval *callSiteVarianceMap = slot(slots::callSiteVarianceMap, "callSiteVarianceMap");
		if (UNEXPECTED(callSiteVarianceMap == NULL)) return zv::Val();
		zval *positionVariance = pt_template_type_variance_singleton(variance);
		if (UNEXPECTED(positionVariance == NULL)) return zv::Val();
		zv::Val resolved = pt_type_template_type_helper_resolve_template_types(type, resolvedTemplateTypeMap, callSiteVarianceMap, positionVariance, false);
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		return pt_type_utils_resolve_late_resolvable_types_ex(resolved.raw(), false);
	}

	/* the array_map() callback of getParameters() */
	zv::Val resolveParameter(zval *param) const
	{
		zend_class_entry *extendedParameterCe = pt_class(PT_CLASS_EXTENDED_PARAMETER_REFLECTION);
		if (UNEXPECTED(extendedParameterCe == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(param) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(param), extendedParameterCe))) {
			zend_type_error(PT_RFV_PARAMETERS_CLOSURE "(): Argument #1 ($param) must be of type PHPStan\\Reflection\\ExtendedParameterReflection, %s given", zend_zval_value_name(param));
			return zv::Val();
		}

		zv::Val originalType = pt_parameter_reflection_call(param, PT_PR_GET_TYPE);
		if (UNEXPECTED(originalType.isUndef())) return zv::Val();
		zv::Val paramType = resolveParameterType(originalType.raw(), PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT);
		if (UNEXPECTED(paramType.isUndef())) return zv::Val();

		zv::Val paramOutType = pt_parameter_reflection_call(param, PT_PR_GET_OUT_TYPE);
		if (UNEXPECTED(paramOutType.isUndef())) return zv::Val();
		if (!paramOutType.isNull()) {
			paramOutType = resolveParameterType(paramOutType.raw(), PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
			if (UNEXPECTED(paramOutType.isUndef())) return zv::Val();
		}

		zv::Val closureThisType = pt_parameter_reflection_call(param, PT_PR_GET_CLOSURE_THIS_TYPE);
		if (UNEXPECTED(closureThisType.isUndef())) return zv::Val();
		if (!closureThisType.isNull()) {
			closureThisType = resolveParameterType(closureThisType.raw(), PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
			if (UNEXPECTED(closureThisType.isUndef())) return zv::Val();
		}

		/* new ExtendedDummyParameter($param->getName(), $paramType, ...) — the
		 * getters in argument order */
		static constexpr pt_parameter_reflection_member getters[11] = {
			PT_PR_GET_NAME, PT_PR_IS_OPTIONAL, PT_PR_PASSED_BY_REFERENCE, PT_PR_IS_VARIADIC, PT_PR_GET_DEFAULT_VALUE,
			PT_PR_GET_NATIVE_TYPE, PT_PR_GET_PHPDOC_TYPE, PT_PR_IS_IMMEDIATELY_INVOKED_CALLABLE, PT_PR_GET_ATTRIBUTES,
			PT_PR_GET_ALLOWED_CONSTANTS, PT_PR_IS_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETER,
		};
		zv::Val values[11];
		for (int i = 0; i < 11; i++) {
			values[i] = pt_parameter_reflection_call(param, getters[i]);
			if (UNEXPECTED(values[i].isUndef())) return zv::Val();
		}
		zval args[14];
		ZVAL_COPY_VALUE(&args[0], values[0].raw());
		ZVAL_COPY_VALUE(&args[1], paramType.raw());
		ZVAL_COPY_VALUE(&args[2], values[1].raw());
		ZVAL_COPY_VALUE(&args[3], values[2].raw());
		ZVAL_COPY_VALUE(&args[4], values[3].raw());
		ZVAL_COPY_VALUE(&args[5], values[4].raw());
		ZVAL_COPY_VALUE(&args[6], values[5].raw());
		ZVAL_COPY_VALUE(&args[7], values[6].raw());
		ZVAL_COPY_VALUE(&args[8], paramOutType.raw());
		ZVAL_COPY_VALUE(&args[9], values[7].raw());
		ZVAL_COPY_VALUE(&args[10], closureThisType.raw());
		ZVAL_COPY_VALUE(&args[11], values[8].raw());
		ZVAL_COPY_VALUE(&args[12], values[9].raw());
		ZVAL_COPY_VALUE(&args[13], values[10].raw());
		return pt_extended_dummy_parameter_new(14, args);
	}

	/* TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes(
	 * $this->resolveConditionalTypesForParameter($type), ..., <variance>), false) */
	zv::Val resolveParameterType(zval *type, zend_long variance) const
	{
		zv::Val conditional = resolveConditionalTypesForParameter(type);
		if (UNEXPECTED(conditional.isUndef())) return zv::Val();
		return resolveTemplateTypesAndLateResolvable(conditional.raw(), variance);
	}

	/* the position variance of the reference to exactly this occurrence of
	 * the template type, invariant when none (borrowed from the references
	 * or the singleton, kept in hold); NULL = pending exception */
	static zval *referenceVarianceOf(zval *references, zval *type, zv::Val &hold)
	{
		zval *variance = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_INVARIANT);
		if (UNEXPECTED(variance == NULL)) return NULL;
		if (Z_TYPE_P(references) != IS_ARRAY) return variance;
		for (zv::ArrayEntry entry : zv::ArrRef(references)) {
			zv::Ref reference = entry.value().deref();
			if (UNEXPECTED(!reference.isObject())) {
				zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(reference.raw()));
				return NULL;
			}
			zv::Val referenceType, positionVariance;
			if (UNEXPECTED(!pt_template_type_reference_parts(reference.raw(), referenceType, positionVariance))) return NULL;
			if (Z_TYPE_P(referenceType.raw()) == IS_OBJECT && Z_OBJ_P(referenceType.raw()) == Z_OBJ_P(type)) {
				hold = std::move(positionVariance);
				return hold.raw();
			}
		}
		return variance;
	}

	/* the shared tail of both traversal callbacks: $callSiteVariance =
	 * $this->callSiteVarianceMap->getVariance($type->getName()) and the three
	 * returns it decides between */
	static void callSiteVarianceTail(zend_object *self, zval *type, zval *name, zval *traverse, zval *newType, zval *variance, zval *return_value)
	{
		zval *callSiteVarianceMap = pt_typed_slot(self, slots::callSiteVarianceMap, pt_ce_resolved_function_variant_with_original, "callSiteVarianceMap");
		if (UNEXPECTED(callSiteVarianceMap == NULL)) return;
		zv::Val callSiteVariance = varianceMapGetVariance(callSiteVarianceMap, name);
		if (UNEXPECTED(callSiteVariance.isUndef())) return;
		if (callSiteVariance.isNull()) {
			RETURN_COPY(newType);
		}
		zend_long callSite = varianceOf(callSiteVariance.raw());
		if (UNEXPECTED(callSite < 0)) return;
		if (callSite == PT_TEMPLATE_TYPE_VARIANCE_INVARIANT) {
			RETURN_COPY(newType);
		}
		zend_long position = varianceOf(variance);
		if (UNEXPECTED(position < 0)) return;
		if (callSite != PT_TEMPLATE_TYPE_VARIANCE_COVARIANT && position == PT_TEMPLATE_TYPE_VARIANCE_COVARIANT) {
			zv::Val bound = templateBound(type);
			if (UNEXPECTED(bound.isUndef())) return;
			(void) pt_type_traverser_traverse(return_value, traverse, bound.raw());
			return;
		}
		if (callSite != PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT && position == PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT) {
			zval never;
			if (UNEXPECTED(!pt_non_accepting_never_type_new(&never))) return;
			ZVAL_COPY_VALUE(return_value, &never);
			return;
		}
		RETURN_COPY(newType);
	}

	/* $this->resolvedTemplateTypeMap->getType($name) (UNDEF = pending
	 * exception) */
	static zv::Val resolvedTypeFor(zend_object *self, zval *name)
	{
		zval *resolvedTemplateTypeMap = pt_typed_slot(self, slots::resolvedTemplateTypeMap, pt_ce_resolved_function_variant_with_original, "resolvedTemplateTypeMap");
		if (UNEXPECTED(resolvedTemplateTypeMap == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(name) != IS_STRING)) {
			zend_type_error("phpstan_turbo: getName() must return string");
			return zv::Val();
		}
		return pt_template_type_map_get_type(resolvedTemplateTypeMap, Z_STR_P(name));
	}

	/* the TypeTraverser callback of resolveResolvableTemplateTypes() —
	 * captures: $this, $references, $objectCb */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function " PT_RFV_CLOSURE("resolveResolvableTemplateTypes", "330") "(), %u passed and exactly 2 expected", argc);
			return;
		}
		zend_object *self = Z_OBJ(captures[0]);
		zval *references = &captures[1];
		zval *type = &argv[0];
		zval *traverse = &argv[1];
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			(void) pt_type_traverser_traverse(return_value, traverse, type);
			return;
		}
		zend_class_entry *ce = Z_OBJCE_P(type);
		if (instanceof_function(ce, pt_ce_generic_object_type) || instanceof_function(ce, pt_ce_generic_static_type)) {
			(void) pt_type_traverser_map(return_value, type, &captures[2]);
			return;
		}

		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return;
		if (isTemplate) {
			bool isNarrowedSubject;
			if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_NARROWED_SUBJECT_TYPE, isNarrowedSubject))) return;
			if (isNarrowedSubject) isTemplate = false;
		}
		if (isTemplate) {
			bool isArgument;
			if (UNEXPECTED(!templateIsArgument(type, isArgument))) return;
			if (!isArgument) {
				zv::Val name = templateName(type);
				if (UNEXPECTED(name.isUndef())) return;
				zv::Val newType = resolvedTypeFor(self, name.raw());
				if (UNEXPECTED(newType.isUndef())) return;
				if (newType.isNull() || instanceof_function(Z_OBJCE_P(newType.raw()), pt_ce_error_type)) {
					(void) pt_type_traverser_traverse(return_value, traverse, type);
					return;
				}

				zv::Val varianceHold;
				zval *variance = referenceVarianceOf(references, type, varianceHold);
				if (UNEXPECTED(variance == NULL)) return;

				zend_long position = varianceOf(variance);
				if (UNEXPECTED(position < 0)) return;
				if (position == PT_TEMPLATE_TYPE_VARIANCE_COVARIANT) {
					/* an unresolved template argument inferred from a generic
					 * argument and returned bare is a derived value */
					newType = pt_type_call_static_ce(pt_ce_unresolved_template_argument_type, PT_LC("unwrapbare"), 1, newType.raw());
					if (UNEXPECTED(newType.isUndef())) return;
				}

				callSiteVarianceTail(self, type, name.raw(), traverse, newType.raw(), variance, return_value);
				return;
			}
		}

		(void) pt_type_traverser_traverse(return_value, traverse, type);
	}

	/* the $objectCb of resolveResolvableTemplateTypes() — captures: $this,
	 * $references, $site, $frame, $allowUnresolved */
	static void objectCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function " PT_RFV_CLOSURE("resolveResolvableTemplateTypes", "284") "(), %u passed and exactly 2 expected", argc);
			return;
		}
		zend_object *self = Z_OBJ(captures[0]);
		zval *references = &captures[1];
		zval *site = &captures[2];
		zval *frame = &captures[3];
		bool allowUnresolved = Z_TYPE(captures[4]) == IS_TRUE;
		zval *type = &argv[0];
		zval *traverse = &argv[1];

		bool isTemplate = false;
		if (Z_TYPE_P(type) == IS_OBJECT && UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return;
		if (isTemplate) {
			bool isNarrowedSubject;
			if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_NARROWED_SUBJECT_TYPE, isNarrowedSubject))) return;
			if (isNarrowedSubject) isTemplate = false;
		}
		if (isTemplate) {
			bool isArgument;
			if (UNEXPECTED(!templateIsArgument(type, isArgument))) return;
			bool functionScope = false;
			if (!isArgument && UNEXPECTED(!templateHasFunctionScope(type, functionScope))) return;
			if (!isArgument && functionScope) {
				zv::Val name = templateName(type);
				if (UNEXPECTED(name.isUndef())) return;
				zv::Val newType = resolvedTypeFor(self, name.raw());
				if (UNEXPECTED(newType.isUndef())) return;
				if (newType.isNull() || instanceof_function(Z_OBJCE_P(newType.raw()), pt_ce_error_type)) {
					(void) pt_type_traverser_traverse(return_value, traverse, type);
					return;
				}

				if (Z_TYPE_P(site) != IS_NULL && Z_TYPE_P(frame) != IS_NULL) {
					newType = unresolvedOrResolvedTemplateArgument(type, newType.raw(), site, frame, allowUnresolved);
				} else {
					zv::Args generalizeArgs{type, newType.raw()};
					newType = pt_type_call_static_ce(pt_ce_template_type_helper, PT_LC("generalizeinferredtemplatetype"), 2, generalizeArgs);
				}
				if (UNEXPECTED(newType.isUndef())) return;

				zv::Val varianceHold;
				zval *variance = referenceVarianceOf(references, type, varianceHold);
				if (UNEXPECTED(variance == NULL)) return;

				callSiteVarianceTail(self, type, name.raw(), traverse, newType.raw(), variance, return_value);
				return;
			}
		}

		(void) pt_type_traverser_traverse(return_value, traverse, type);
	}

	/* the TypeTraverser callback of narrowTemplateTypesInConditionalTypesForParameter()
	 * — captures: $this */
	static void narrowCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function " PT_RFV_CLOSURE("narrowTemplateTypesInConditionalTypesForParameter", "409") "(), %u passed and exactly 2 expected", argc);
			return;
		}
		zend_object *self = Z_OBJ(captures[0]);
		zval *type = &argv[0];
		zval *traverse = &argv[1];
		zv::Val narrowed;
		if (Z_TYPE_P(type) == IS_OBJECT && instanceof_function(Z_OBJCE_P(type), pt_ce_conditional_type_for_parameter)) {
			zv::Val parameterName = conditionalParameterName(type);
			if (UNEXPECTED(parameterName.isUndef())) return;
			zv::Val templateType = ResolvedFunctionVariantWithOriginal(self).getTemplateTypeBoundOnlyByParameter(parameterName.raw());
			if (UNEXPECTED(templateType.isUndef())) return;
			if (!templateType.isNull()) {
				narrowed = pt_conditional_type_for_parameter_narrow_template_type(type, templateType.raw());
				if (UNEXPECTED(narrowed.isUndef())) return;
				type = narrowed.raw();
			}
		}
		(void) pt_type_traverser_traverse(return_value, traverse, type);
	}

	/* '$' . $parameter->getName() === $parameterName; false = pending exception */
	[[nodiscard]] static bool parameterIsNamed(zval *parameter, zval *parameterName, bool &out)
	{
		zv::Val name = pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
		if (UNEXPECTED(name.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(name.raw()) != IS_STRING || Z_TYPE_P(parameterName) != IS_STRING)) {
			out = false;
			return true;
		}
		zend_string *expected = Z_STR_P(parameterName);
		zend_string *actual = Z_STR_P(name.raw());
		out = ZSTR_LEN(expected) == ZSTR_LEN(actual) + 1
			&& ZSTR_VAL(expected)[0] == '$'
			&& memcmp(ZSTR_VAL(expected) + 1, ZSTR_VAL(actual), ZSTR_LEN(actual)) == 0;
		return true;
	}

	/* Mirrors referencesTemplateType() (private static): whether $type mentions
	 * $templateType, a template type's bound included; false = pending exception */
	[[nodiscard]] static bool referencesTemplateType(zval *type, zval *templateType, bool &out)
	{
		zval references;
		ZVAL_FALSE(&references);
		zv::Val callback = pt_type_native_callback(referencesCallbackBody, &references, templateType);
		if (UNEXPECTED(callback.isUndef())) return false;
		zv::Val mapped = pt_type_traverser_map_of(type, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return false;
		out = Z_TYPE_P(pt_type_native_callback_state(callback.raw(), 0)) == IS_TRUE;
		return true;
	}

	/* the `static function (Type $type, callable $traverse) use ($templateType,
	 * &$references)` of referencesTemplateType() */
	static void referencesCallbackBody(zval *references, zval *templateType, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function " PT_RFV_CLOSURE("referencesTemplateType", "472") "(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *type = &argv[0];
		zval *traverse = &argv[1];
		bool isTemplate = false;
		if (Z_TYPE_P(type) == IS_OBJECT && UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return;
		if (isTemplate) {
			zv::Val name = templateName(type);
			if (UNEXPECTED(name.isUndef())) return;
			zv::Val otherName = templateName(templateType);
			if (UNEXPECTED(otherName.isUndef())) return;
			if (zend_is_identical(name.raw(), otherName.raw())) {
				zv::Val scope = pt_call_method_cached(pt_rfv_template_get_scope_site, Z_OBJ_P(type), PT_LC("getscope"), 0, NULL);
				if (UNEXPECTED(scope.isUndef())) return;
				zv::Val otherScope = pt_call_method_cached(pt_rfv_template_get_scope_site, Z_OBJ_P(templateType), PT_LC("getscope"), 0, NULL);
				if (UNEXPECTED(otherScope.isUndef())) return;
				bool equal;
				if (UNEXPECTED(!pt_template_type_scope_equals(scope.raw(), otherScope.raw(), equal))) return;
				if (equal) {
					ZVAL_TRUE(references);
					ZVAL_COPY(return_value, type);
					return;
				}
			}
		}
		/* a template type traverses into its bound */
		(void) pt_type_traverser_traverse(return_value, traverse, type);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ResolvedFunctionVariantWithOriginal;

/* {{{ direct entries (support.h) */

zv::Val pt_resolved_function_variant_with_original_new(zval *parametersAcceptor, zval *resolvedTemplateTypeMap, zval *callSiteVarianceMap, zval *passedArgs)
{
	return ResolvedFunctionVariantWithOriginal::create(parametersAcceptor, resolvedTemplateTypeMap, callSiteVarianceMap, passedArgs);
}

zv::Val pt_resolved_function_variant_with_original_call(zend_object *variant, pt_parameters_acceptor_member member)
{
	ResolvedFunctionVariantWithOriginal resolved(variant);
	switch (member) {
		case PT_PA_GET_TEMPLATE_TYPE_MAP: return resolved.getTemplateTypeMap();
		case PT_PA_GET_RESOLVED_TEMPLATE_TYPE_MAP: return resolved.getResolvedTemplateTypeMap();
		case PT_PA_GET_PARAMETERS: return resolved.getParameters();
		case PT_PA_IS_VARIADIC: return resolved.isVariadic();
		case PT_PA_GET_RETURN_TYPE: return resolved.getReturnType();
		case PT_PA_GET_PHPDOC_RETURN_TYPE: return resolved.getPhpDocReturnType();
		case PT_PA_GET_NATIVE_RETURN_TYPE: return resolved.getNativeReturnType();
		case PT_PA_GET_CALL_SITE_VARIANCE_MAP: return resolved.getCallSiteVarianceMap();
		case PT_PA_GET_ORIGINAL_PARAMETERS_ACCEPTOR: return resolved.getOriginalParametersAcceptor();
		case PT_PA_GET_RETURN_TYPE_WITH_UNRESOLVABLE_TEMPLATE_TYPES: return resolved.getReturnTypeWithUnresolvableTemplateTypes();
		case PT_PA_GET_THROW_POINTS:
		case PT_PA_IS_PURE:
		case PT_PA_GET_IMPURE_POINTS:
		case PT_PA_GET_INVALIDATE_EXPRESSIONS:
		case PT_PA_GET_USED_VARIABLES:
		case PT_PA_ACCEPTS_NAMED_ARGUMENTS:
		case PT_PA_MUST_USE_RETURN_VALUE:
		case PT_PA_GET_ASSERTS:
		case PT_PA_IS_STATIC_CLOSURE:
			return pt_parameters_acceptor_call_method(variant, member);
		case PT_PA_MEMBER_COUNT: break;
	}
	ZEND_UNREACHABLE();
	return zv::Val();
}

zv::Val pt_resolved_function_variant_get_return_type_with_unresolved_template_arguments(zval *acceptor, zval *site, zval *frame, bool allowUnresolved)
{
	if (EXPECTED(Z_OBJCE_P(acceptor) == pt_ce_resolved_function_variant_with_original)) return ResolvedFunctionVariantWithOriginal(Z_OBJ_P(acceptor)).getReturnTypeWithUnresolvedTemplateArguments(site, frame, allowUnresolved);
	zv::Args argv{site, frame, allowUnresolved};
	return pt_type_call(Z_OBJ_P(acceptor), PT_LC("getreturntypewithunresolvedtemplatearguments"), 3, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_RFV_THIS ResolvedFunctionVariantWithOriginal(Z_OBJ_P(ZEND_THIS))

void pt_register_resolved_function_variant_with_original()
{
	reg::Class cls("PHPStan\\Reflection\\ResolvedFunctionVariantWithOriginal");
	ptdecl::ResolvedFunctionVariantWithOriginal::declareClass(cls);
	ptdecl::ResolvedFunctionVariantWithOriginal::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parametersAcceptor, *resolvedTemplateTypeMap, *callSiteVarianceMap, *passedArgs;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_OBJECT_OF_CLASS(parametersAcceptor, pt_class(PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR))
			Z_PARAM_OBJECT_OF_CLASS(resolvedTemplateTypeMap, pt_ce_template_type_map)
			Z_PARAM_OBJECT_OF_CLASS(callSiteVarianceMap, pt_ce_template_type_variance_map)
			Z_PARAM_ARRAY(passedArgs)
		ZEND_PARSE_PARAMETERS_END();
		PT_RFV_THIS.construct(parametersAcceptor, resolvedTemplateTypeMap, callSiteVarianceMap, passedArgs);
	});

	cls.method<&ResolvedFunctionVariantWithOriginal::getOriginalParametersAcceptor>(sigs::getOriginalParametersAcceptor);
	cls.method<&ResolvedFunctionVariantWithOriginal::getTemplateTypeMap>(sigs::getTemplateTypeMap);
	cls.method<&ResolvedFunctionVariantWithOriginal::getResolvedTemplateTypeMap>(sigs::getResolvedTemplateTypeMap);
	cls.method<&ResolvedFunctionVariantWithOriginal::getCallSiteVarianceMap>(sigs::getCallSiteVarianceMap);
	cls.method<&ResolvedFunctionVariantWithOriginal::getParameters>(sigs::getParameters);
	cls.method<&ResolvedFunctionVariantWithOriginal::isVariadic>(sigs::isVariadic);
	cls.method<&ResolvedFunctionVariantWithOriginal::getReturnTypeWithUnresolvableTemplateTypes>(sigs::getReturnTypeWithUnresolvableTemplateTypes);
	cls.method<&ResolvedFunctionVariantWithOriginal::getPhpDocReturnTypeWithUnresolvableTemplateTypes>(sigs::getPhpDocReturnTypeWithUnresolvableTemplateTypes);
	cls.method<&ResolvedFunctionVariantWithOriginal::getReturnType>(sigs::getReturnType);

	cls.method(sigs::getReturnTypeWithUnresolvedTemplateArguments, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *site, *frame;
		bool allowUnresolved;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT_OF_CLASS(site, pt_class(PT_CLASS_EXPR))
			Z_PARAM_OBJECT_OF_CLASS(frame, pt_ce_template_argument_frame)
			Z_PARAM_BOOL(allowUnresolved)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_RFV_THIS.getReturnTypeWithUnresolvedTemplateArguments(site, frame, allowUnresolved));
	});

	cls.method<&ResolvedFunctionVariantWithOriginal::getPhpDocReturnType>(sigs::getPhpDocReturnType);
	cls.method<&ResolvedFunctionVariantWithOriginal::getNativeReturnType>(sigs::getNativeReturnType);

	cls.method(sigs::resolveResolvableTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *positionVariance, *site = NULL, *frame = NULL;
		bool allowUnresolved = true;
		ZEND_PARSE_PARAMETERS_START(2, 5)
			Z_PARAM_OBJECT_OF_CLASS(type, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(positionVariance, pt_ce_template_type_variance)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(site, pt_class(PT_CLASS_EXPR))
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(frame, pt_ce_template_argument_frame)
			Z_PARAM_BOOL(allowUnresolved)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_RFV_THIS.resolveResolvableTemplateTypes(type, positionVariance, site, frame, allowUnresolved));
	});

	cls.method(sigs::unresolvedOrResolvedTemplateArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *templateType, *inferred, *site, *frame;
		bool allowUnresolved;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT_OF_CLASS(templateType, pt_class(PT_CLASS_TEMPLATE_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(inferred, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(site, pt_class(PT_CLASS_EXPR))
			Z_PARAM_OBJECT_OF_CLASS(frame, pt_ce_template_argument_frame)
			Z_PARAM_BOOL(allowUnresolved)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ResolvedFunctionVariantWithOriginal::unresolvedOrResolvedTemplateArgument(templateType, inferred, site, frame, allowUnresolved));
	});

	cls.method<&ResolvedFunctionVariantWithOriginal::hasBoundArgs>(sigs::hasBoundArgs);
	cls.method<&ResolvedFunctionVariantWithOriginal::resolveConditionalTypes, zp::Obj>(sigs::resolveConditionalTypes);
	cls.method<&ResolvedFunctionVariantWithOriginal::narrowTemplateTypesInConditionalTypesForParameter, zp::Obj>(sigs::narrowTemplateTypesInConditionalTypesForParameter);
	cls.method(sigs::getTemplateTypeBoundOnlyByParameter, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *parameterName;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(parameterName)
		ZEND_PARSE_PARAMETERS_END();
		zval name;
		ZVAL_STR(&name, parameterName);
		PT_RETURN_VAL(PT_RFV_THIS.getTemplateTypeBoundOnlyByParameter(&name));
	});
	cls.method(sigs::referencesTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *templateType;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT(templateType)
		ZEND_PARSE_PARAMETERS_END();
		bool references;
		if (UNEXPECTED(!ResolvedFunctionVariantWithOriginal::typeReferencesTemplateType(type, templateType, references))) RETURN_THROWS();
		RETURN_BOOL(references);
	});

	cls.method(sigs::resolveConditionalTypesForParameter, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(type, pt_class(PT_CLASS_TYPE))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_RFV_THIS.resolveConditionalTypesForParameter(type));
	});

	cls.shadow(&pt_ce_resolved_function_variant_with_original);
}

/* }}} */
