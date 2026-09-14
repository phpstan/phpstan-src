/*
 * PHPStanTurbo\TemplateTypeHelper — native implementation of
 * PHPStan\Type\Generic\TemplateTypeHelper.
 *
 * When the extension is active, PHPStan\Type\Generic\TemplateTypeHelper is
 * this class, declared under that name at activation (final, like the
 * twin). Every method is a TypeTraverser::map() over a closure of the
 * twin's — here a native body behind a PHPStanTurbo\NativeCallback holder
 * (the closure's `use` variables in the holder's state slots), run through
 * the native TypeTraverser — except generalizeInferredTemplateType(),
 * which is straight-line Type calls.
 */

#include "support.h"
#include "generated/TemplateTypeHelper.h"

namespace sigs = ptdecl::TemplateTypeHelper::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_template_type_helper = NULL;

/* the packed `use` array of resolveTemplateTypes()'s closure, by index */
#define PT_TTH_USE_STANDINS 0
#define PT_TTH_USE_REFERENCES 1
#define PT_TTH_USE_CALL_SITE_VARIANCES 2
#define PT_TTH_USE_KEEP_ERROR_TYPES 3

/* the (Type $type, callable $traverse) pair a traversal callback receives;
 * false with an Error pending on anything else */
[[nodiscard]] static bool pt_tth_callback_args(uint32_t argc, zval *argv, zval *&type, zval *&traverse)
{
	if (UNEXPECTED(argc != 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: the TemplateTypeHelper traversal callback expects (Type, callable)");
		return false;
	}
	type = &argv[0];
	traverse = &argv[1];
	return true;
}

/* $type instanceof TemplateType; false with an exception pending when the
 * interface cannot be resolved */
[[nodiscard]] static bool pt_tth_is_template(zval *type, bool &out)
{
	return pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, out);
}

/* $type->getName() (a string); UNDEF = pending exception */
static zv::Val pt_tth_name(zval *type)
{
	zv::Val name = pt_type_call(Z_OBJ_P(type), PT_LC("getname"), 0, NULL);
	if (UNEXPECTED(name.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(name.raw()).isString())) {
		zend_type_error("phpstan_turbo: getName() must return string");
		return zv::Val();
	}
	return name;
}

/* $type->getDefault() ?? $type->getBound(); UNDEF = pending exception */
static zv::Val pt_tth_default_or_bound(zval *type)
{
	zv::Val defaultType = pt_type_call(Z_OBJ_P(type), PT_LC("getdefault"), 0, NULL);
	if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
	if (!defaultType.isNull()) return defaultType;
	return pt_type_call(Z_OBJ_P(type), PT_LC("getbound"), 0, NULL);
}

/* $variance->covariant() / ->contravariant() / ->invariant() of a variance
 * value; false = pending exception */
[[nodiscard]] static bool pt_tth_variance_is(zval *variance, zend_long which, bool &out)
{
	zend_long value;
	if (UNEXPECTED(!pt_template_type_variance_value_of(variance, value))) return false;
	out = value == which;
	return true;
}

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeHelper (static methods only). */
class TemplateTypeHelper
{
public:
	/* resolveTemplateTypes(): UNDEF = pending exception */
	static zv::Val resolveTemplateTypes(zval *type, zval *standins, zval *callSiteVariances, zval *positionVariance, bool keepErrorTypes);

	/* resolveToDefaults() / resolveToBounds(): every template type replaced
	 * by its default-or-bound / its bound, repeatedly; UNDEF = pending
	 * exception */
	static zv::Val resolveToDefaults(zval *type) { return mapWith(type, resolveToDefaultsCallback); }
	static zv::Val resolveToBounds(zval *type) { return mapWith(type, resolveToBoundsCallback); }

	/* toArgument(): every template type not owned by a callable inside
	 * $type turned into its argument; UNDEF = pending exception */
	static zv::Val toArgument(zval *type)
	{
		zval ownedTemplates;
		ZVAL_EMPTY_ARRAY(&ownedTemplates);
		zv::Val callback = pt_type_native_callback(toArgumentCallback, &ownedTemplates, NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		return map(type, callback.raw());
	}

	/* removeFinalByKeywordOverrides(); UNDEF = pending exception */
	static zv::Val removeFinalByKeywordOverrides(zval *type) { return mapWith(type, removeFinalByKeywordOverridesCallback); }

	/* generalizeInferredTemplateType(); UNDEF = pending exception */
	static zv::Val generalizeInferredTemplateType(zval *templateType, zval *type)
	{
		zv::Val variance = pt_type_call(Z_OBJ_P(templateType), PT_LC("getvariance"), 0, NULL);
		if (UNEXPECTED(variance.isUndef())) return zv::Val();
		bool covariant;
		if (UNEXPECTED(!pt_tth_variance_is(variance.raw(), PT_TEMPLATE_TYPE_VARIANCE_COVARIANT, covariant))) return zv::Val();
		if (covariant) return zv::Val::copyOf(zv::Ref(type));

		/* $isArrayKey = $templateType->getBound()->describe(VerbosityLevel::precise()) === '(int|string)' */
		zv::Val bound = pt_type_call(Z_OBJ_P(templateType), PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(bound.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getBound() must return an object");
			return zv::Val();
		}
		zval described;
		if (UNEXPECTED(!pt_type_describe_precise(bound.raw(), &described))) return zv::Val();
		zv::Val describedOwned = zv::Val::adopt(described);
		bool isArrayKey = zv::Ref(describedOwned.raw()).stringEquals("(int|string)");

		zend_long isScalar = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isscalar"), 0, NULL);
		if (UNEXPECTED(isScalar < 0)) return zv::Val();
		bool generalize = false;
		if (isScalar == PT_TRI_YES && isArrayKey) {
			generalize = true;
		} else {
			zend_long isConstantValue = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isconstantvalue"), 0, NULL);
			if (UNEXPECTED(isConstantValue < 0)) return zv::Val();
			if (isConstantValue == PT_TRI_YES) {
				bool boundIsScalar = false;
				if (!isArrayKey) {
					/* $templateType->getBound()->isScalar()->yes(), read again as the twin does */
					zv::Val boundAgain = pt_type_call(Z_OBJ_P(templateType), PT_LC("getbound"), 0, NULL);
					if (UNEXPECTED(boundAgain.isUndef())) return zv::Val();
					zend_long boundScalar = pt_type_call_trinary(Z_OBJ_P(boundAgain.raw()), PT_LC("isscalar"), 0, NULL);
					if (UNEXPECTED(boundScalar < 0)) return zv::Val();
					boundIsScalar = boundScalar == PT_TRI_YES;
				}
				generalize = !boundIsScalar || isArrayKey;
			}
		}
		if (!generalize) return zv::Val::copyOf(zv::Ref(type));
		zv::Val precision = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("templateargument"), 0, NULL);
		if (UNEXPECTED(precision.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(type), PT_LC("generalize"), 1, precision.raw());
	}

private:
	/* TypeTraverser::map($type, $callback); UNDEF = pending exception */
	static zv::Val map(zval *type, zval *callback)
	{
		zval mapped;
		if (UNEXPECTED(!pt_type_traverser_map(&mapped, type, callback))) return zv::Val();
		return zv::Val::adopt(mapped);
	}

	/* the map over a stateless native body */
	static zv::Val mapWith(zval *type, pt_native_callback fn)
	{
		zv::Val callback = pt_type_native_callback(fn, NULL, NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		return map(type, callback.raw());
	}

	static void resolveTemplateTypesCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value);
	static void resolveToDefaultsCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value);
	static void resolveToBoundsCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value);
	static void toArgumentCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value);
	static void removeFinalByKeywordOverridesCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value);
};

zv::Val TemplateTypeHelper::resolveTemplateTypes(zval *type, zval *standins, zval *callSiteVariances, zval *positionVariance, bool keepErrorTypes)
{
	zv::Val hasTemplate = pt_type_call(Z_OBJ_P(type), PT_LC("hastemplateorlateresolvabletype"), 0, NULL);
	if (UNEXPECTED(hasTemplate.isUndef())) return zv::Val();
	if (!zend_is_true(hasTemplate.raw())) return zv::Val::copyOf(zv::Ref(type));

	zv::Val references = pt_type_call(Z_OBJ_P(type), PT_LC("getreferencedtemplatetypes"), 1, positionVariance);
	if (UNEXPECTED(references.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(references.raw()).isArray())) {
		zend_type_error("phpstan_turbo: getReferencedTemplateTypes() must return array");
		return zv::Val();
	}

	/* the closure's `use ($standins, $references, $callSiteVariances,
	 * $keepErrorTypes)`, packed into the holder's first state slot */
	zv::Arr uses = zv::Arr::create(4);
	uses.push(zv::Ref(standins));
	uses.push(zv::Ref(references.raw()));
	uses.push(zv::Ref(callSiteVariances));
	uses.push(zv::Val::boolean(keepErrorTypes));
	zv::Val callback = pt_type_native_callback(resolveTemplateTypesCallback, uses.raw(), NULL);
	if (UNEXPECTED(callback.isUndef())) return zv::Val();
	return map(type, callback.raw());
}

void TemplateTypeHelper::resolveTemplateTypesCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	zval *type, *traverse;
	if (UNEXPECTED(!pt_tth_callback_args(argc, argv, type, traverse))) return;
	HashTable *uses = Z_ARRVAL_P(state0);
	zval *standins = zend_hash_index_find(uses, PT_TTH_USE_STANDINS);
	zval *references = zend_hash_index_find(uses, PT_TTH_USE_REFERENCES);
	zval *callSiteVariances = zend_hash_index_find(uses, PT_TTH_USE_CALL_SITE_VARIANCES);
	zval *keepErrorTypes = zend_hash_index_find(uses, PT_TTH_USE_KEEP_ERROR_TYPES);
	ZEND_ASSERT(standins != NULL && references != NULL && callSiteVariances != NULL && keepErrorTypes != NULL);

	bool isTemplate;
	if (UNEXPECTED(!pt_tth_is_template(type, isTemplate))) return;
	if (isTemplate) {
		/* && !$type instanceof NarrowedSubjectType: a narrowed reference to a
		 * conditional's subject recomputes its narrowing from what the subject
		 * resolves to, so it is traversed into rather than substituted */
		bool isNarrowedSubject;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_NARROWED_SUBJECT_TYPE, isNarrowedSubject))) return;
		if (isNarrowedSubject) isTemplate = false;
	}
	if (isTemplate) {
		zv::Val isArgument = pt_type_call(Z_OBJ_P(type), PT_LC("isargument"), 0, NULL);
		if (UNEXPECTED(isArgument.isUndef())) return;
		if (!zend_is_true(isArgument.raw())) {
			zv::Val name = pt_tth_name(type);
			if (UNEXPECTED(name.isUndef())) return;
			zv::Val newType = pt_type_call(Z_OBJ_P(standins), PT_LC("gettype"), 1, name.raw());
			if (UNEXPECTED(newType.isUndef())) return;

			/* $variance = TemplateTypeVariance::createInvariant(), replaced
			 * by the position variance of the reference to this very
			 * occurrence (identity, not equality — see the twin) */
			zval *variance = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_INVARIANT);
			if (UNEXPECTED(variance == NULL)) return;
			zv::Val referenceVariance;
			for (zv::ArrayEntry entry : zv::ArrRef(references)) {
				zv::Ref reference = entry.value().deref();
				if (UNEXPECTED(!reference.isObject())) {
					zend_type_error("phpstan_turbo: a template type reference must be an object");
					return;
				}
				zv::Val referenceType, positionVariance;
				if (UNEXPECTED(!pt_template_type_reference_parts(reference.raw(), referenceType, positionVariance))) return;
				if (Z_TYPE_P(referenceType.raw()) == IS_OBJECT && Z_OBJ_P(referenceType.raw()) == Z_OBJ_P(type)) {
					referenceVariance = std::move(positionVariance);
					variance = referenceVariance.raw();
					break;
				}
			}

			if (newType.isNull()) {
				(void) pt_type_traverser_traverse(return_value, traverse, type);
				return;
			}

			bool isError;
			if (UNEXPECTED(!pt_type_instanceof_ce(newType.raw(), pt_ce_error_type, isError))) return;
			if (isError && !zend_is_true(keepErrorTypes)) {
				zv::Val fallback = pt_tth_default_or_bound(type);
				if (UNEXPECTED(fallback.isUndef())) return;
				(void) pt_type_traverser_traverse(return_value, traverse, fallback.raw());
				return;
			}

			bool varianceCovariant;
			if (UNEXPECTED(!pt_tth_variance_is(variance, PT_TEMPLATE_TYPE_VARIANCE_COVARIANT, varianceCovariant))) return;
			if (varianceCovariant) {
				/* a bare unresolved argument read out of the object (Foo<T>::get(): T)
				 * is a derived value - see UnresolvedTemplateArgumentType::unwrapBare() */
				newType = pt_type_call_static_ce(pt_ce_unresolved_template_argument_type, PT_LC("unwrapbare"), 1, newType.raw());
				if (UNEXPECTED(newType.isUndef())) return;
			}

			zv::Val callSiteVariance = pt_type_call(Z_OBJ_P(callSiteVariances), PT_LC("getvariance"), 1, name.raw());
			if (UNEXPECTED(callSiteVariance.isUndef())) return;
			if (callSiteVariance.isNull()) {
				RETURN_COPY(newType.raw());
			}
			bool callSiteInvariant;
			if (UNEXPECTED(!pt_tth_variance_is(callSiteVariance.raw(), PT_TEMPLATE_TYPE_VARIANCE_INVARIANT, callSiteInvariant))) return;
			if (callSiteInvariant) {
				RETURN_COPY(newType.raw());
			}

			bool callSiteCovariant;
			if (UNEXPECTED(!pt_tth_variance_is(callSiteVariance.raw(), PT_TEMPLATE_TYPE_VARIANCE_COVARIANT, callSiteCovariant))) return;
			if (!callSiteCovariant && varianceCovariant) {
				zv::Val bound = pt_type_call(Z_OBJ_P(type), PT_LC("getbound"), 0, NULL);
				if (UNEXPECTED(bound.isUndef())) return;
				(void) pt_type_traverser_traverse(return_value, traverse, bound.raw());
				return;
			}

			bool callSiteContravariant, varianceContravariant;
			if (UNEXPECTED(!pt_tth_variance_is(callSiteVariance.raw(), PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT, callSiteContravariant)
				|| !pt_tth_variance_is(variance, PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT, varianceContravariant))) {
				return;
			}
			if (!callSiteContravariant && varianceContravariant) {
				zv::Val never; /* stays UNDEF when the constructor fails */
				pt_non_accepting_never_type_new(never.raw());
				if (UNEXPECTED(never.isUndef())) return;
				never.intoReturnValue(return_value);
				return;
			}

			RETURN_COPY(newType.raw());
		}
	}

	(void) pt_type_traverser_traverse(return_value, traverse, type);
}

/* the shared body of resolveToDefaults() and resolveToBounds(): while the
 * type is a template type, replace it; then descend */
static void pt_tth_resolve(zval *type, zval *traverse, bool defaults, zval *return_value)
{
	zv::Val current = zv::Val::copyOf(zv::Ref(type));
	for (;;) {
		bool isTemplate;
		if (UNEXPECTED(!pt_tth_is_template(current.raw(), isTemplate))) return;
		if (!isTemplate) break;
		zv::Val next = defaults ? pt_tth_default_or_bound(current.raw()) : pt_type_call(Z_OBJ_P(current.raw()), PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(next.isUndef())) return;
		if (UNEXPECTED(!zv::Ref(next.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getBound() must return an object");
			return;
		}
		current = std::move(next);
	}
	(void) pt_type_traverser_traverse(return_value, traverse, current.raw());
}

void TemplateTypeHelper::resolveToDefaultsCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state0;
	(void) state1;
	zval *type, *traverse;
	if (UNEXPECTED(!pt_tth_callback_args(argc, argv, type, traverse))) return;
	pt_tth_resolve(type, traverse, true, return_value);
}

void TemplateTypeHelper::resolveToBoundsCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state0;
	(void) state1;
	zval *type, *traverse;
	if (UNEXPECTED(!pt_tth_callback_args(argc, argv, type, traverse))) return;
	pt_tth_resolve(type, traverse, false, return_value);
}

/* $templateTypeMap->hasType($type->getName()) for a template type; false
 * = pending exception */
[[nodiscard]] static bool pt_tth_map_has_template(zval *templateTypeMap, zval *type, bool &out)
{
	zv::Val name = pt_tth_name(type);
	if (UNEXPECTED(name.isUndef())) return false;
	zv::Val has = pt_type_call(Z_OBJ_P(templateTypeMap), PT_LC("hastype"), 1, name.raw());
	if (UNEXPECTED(has.isUndef())) return false;
	out = zend_is_true(has.raw());
	return true;
}

/* the `use (&$ownedTemplates)` slot: state0 by reference */
void TemplateTypeHelper::toArgumentCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	zval *type, *traverse;
	if (UNEXPECTED(!pt_tth_callback_args(argc, argv, type, traverse))) return;
	zend_object *object = Z_OBJ_P(type);

	bool isAcceptor;
	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_PARAMETERS_ACCEPTOR, isAcceptor))) return;
	if (isAcceptor) {
		zv::Val templateTypeMap = pt_type_call(object, PT_LC("gettemplatetypemap"), 0, NULL);
		if (UNEXPECTED(templateTypeMap.isUndef())) return;
		if (UNEXPECTED(!zv::Ref(templateTypeMap.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getTemplateTypeMap() must return an object");
			return;
		}
		zv::Val parameters = pt_type_call(object, PT_LC("getparameters"), 0, NULL);
		if (UNEXPECTED(parameters.isUndef())) return;
		if (UNEXPECTED(!zv::Ref(parameters.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getParameters() must return array");
			return;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(parameters.raw())) {
			zv::Ref parameter = entry.value().deref();
			if (UNEXPECTED(!parameter.isObject())) {
				zend_type_error("phpstan_turbo: a parameter must be an object");
				return;
			}
			zv::Val parameterType = pt_type_call(parameter.asObject(), PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(parameterType.isUndef())) return;
			bool isTemplate;
			if (UNEXPECTED(!pt_tth_is_template(parameterType.raw(), isTemplate))) return;
			if (!isTemplate) continue;
			bool owned;
			if (UNEXPECTED(!pt_tth_map_has_template(templateTypeMap.raw(), parameterType.raw(), owned))) return;
			if (!owned) continue;
			SEPARATE_ARRAY(state0);
			zval v = parameterType.take();
			zend_hash_next_index_insert(Z_ARRVAL_P(state0), &v);
		}

		zv::Val returnType = pt_type_call(object, PT_LC("getreturntype"), 0, NULL);
		if (UNEXPECTED(returnType.isUndef())) return;
		bool isTemplate;
		if (UNEXPECTED(!pt_tth_is_template(returnType.raw(), isTemplate))) return;
		if (isTemplate) {
			bool owned;
			if (UNEXPECTED(!pt_tth_map_has_template(templateTypeMap.raw(), returnType.raw(), owned))) return;
			if (owned) {
				SEPARATE_ARRAY(state0);
				zval v = returnType.take();
				zend_hash_next_index_insert(Z_ARRVAL_P(state0), &v);
			}
		}
	}

	for (zv::ArrayEntry entry : zv::ArrRef(state0)) {
		zv::Ref ownedTemplate = entry.value().deref();
		if (ownedTemplate.isObject() && ownedTemplate.asObject() == object) {
			(void) pt_type_traverser_traverse(return_value, traverse, type);
			return;
		}
	}

	bool isTemplate;
	if (UNEXPECTED(!pt_tth_is_template(type, isTemplate))) return;
	if (isTemplate) {
		/* templates declared by a callable<T>(...)/Closure<T>(...) type in the signature
		 * belong to the callable value, not to the entered function */
		zv::Val scope = pt_type_call(object, PT_LC("getscope"), 0, NULL);
		if (UNEXPECTED(scope.isUndef())) return;
		bool anonymous;
		if (UNEXPECTED(!pt_template_type_scope_is_anonymous(scope.raw(), anonymous))) return;
		if (anonymous) {
			(void) pt_type_traverser_traverse(return_value, traverse, type);
			return;
		}
		zv::Val argument = pt_type_call(object, PT_LC("toargument"), 0, NULL);
		if (UNEXPECTED(argument.isUndef())) return;
		(void) pt_type_traverser_traverse(return_value, traverse, argument.raw());
		return;
	}

	(void) pt_type_traverser_traverse(return_value, traverse, type);
}

void TemplateTypeHelper::removeFinalByKeywordOverridesCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state0;
	(void) state1;
	zval *type, *traverse;
	if (UNEXPECTED(!pt_tth_callback_args(argc, argv, type, traverse))) return;
	if (zv::Ref(type).instanceOf(pt_ce_object_type)) {
		zv::Val stripped = pt_type_call(Z_OBJ_P(type), PT_LC("withoutfinalbykeywordoverride"), 0, NULL);
		if (UNEXPECTED(stripped.isUndef())) return;
		(void) pt_type_traverser_traverse(return_value, traverse, stripped.raw());
		return;
	}
	(void) pt_type_traverser_traverse(return_value, traverse, type);
}

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeHelper;

/* {{{ exported helpers */

zv::Val pt_type_template_type_helper_resolve_template_types(zval *type, zval *standins, zval *callSiteVariances, zval *positionVariance, bool keepErrorTypes)
{
	return TemplateTypeHelper::resolveTemplateTypes(type, standins, callSiteVariances, positionVariance, keepErrorTypes);
}

zv::Val pt_type_template_type_helper_resolve_to_defaults(zval *type)
{
	return TemplateTypeHelper::resolveToDefaults(type);
}

zv::Val pt_type_template_type_helper_resolve_to_bounds(zval *type)
{
	return TemplateTypeHelper::resolveToBounds(type);
}

zv::Val pt_type_template_type_helper_to_argument(zval *type)
{
	return TemplateTypeHelper::toArgument(type);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

/* the twin's `Type $type` / `TemplateType $templateType` parameter checks
 * (the interfaces are class-map entries the arginfo cannot name); false
 * with a TypeError pending */
static bool pt_tth_check_arg(zval *value, int classIdx, uint32_t argNum)
{
	bool is;
	if (UNEXPECTED(!pt_type_instanceof(value, classIdx, is))) return false;
	if (is) return true;
	zend_class_entry *ce = pt_class(classIdx);
	zend_argument_type_error(argNum, "must be of type %s, %s given", ce != NULL ? ZSTR_VAL(ce->name) : "PHPStan\\Type\\Type", zend_zval_value_name(value));
	return false;
}

void pt_register_template_type_helper()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateTypeHelper");
	ptdecl::TemplateTypeHelper::declareClass(cls);
	ptdecl::TemplateTypeHelper::declareProperties(cls);

	cls.method(sigs::resolveTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *standins, *callSiteVariances, *positionVariance;
		bool keepErrorTypes = false;
		ZEND_PARSE_PARAMETERS_START(4, 5)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT_OF_CLASS(standins, pt_ce_template_type_map)
			Z_PARAM_OBJECT_OF_CLASS(callSiteVariances, pt_ce_template_type_variance_map)
			Z_PARAM_OBJECT_OF_CLASS(positionVariance, pt_ce_template_type_variance)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(keepErrorTypes)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!pt_tth_check_arg(type, PT_CLASS_TYPE, 1))) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeHelper::resolveTemplateTypes(type, standins, callSiteVariances, positionVariance, keepErrorTypes));
	});

	cls.method(sigs::resolveToDefaults, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tth_check_arg(type, PT_CLASS_TYPE, 1))) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeHelper::resolveToDefaults(type));
	});

	cls.method(sigs::resolveToBounds, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tth_check_arg(type, PT_CLASS_TYPE, 1))) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeHelper::resolveToBounds(type));
	});

	cls.method(sigs::toArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tth_check_arg(type, PT_CLASS_TYPE, 1))) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeHelper::toArgument(type));
	});

	cls.method(sigs::removeFinalByKeywordOverrides, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tth_check_arg(type, PT_CLASS_TYPE, 1))) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeHelper::removeFinalByKeywordOverrides(type));
	});

	cls.method(sigs::generalizeInferredTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *templateType, *type;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, templateType, type)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tth_check_arg(templateType, PT_CLASS_TEMPLATE_TYPE, 1) || !pt_tth_check_arg(type, PT_CLASS_TYPE, 2))) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeHelper::generalizeInferredTemplateType(templateType, type));
	});

	cls.shadow(&pt_ce_template_type_helper);
}

/* }}} */
