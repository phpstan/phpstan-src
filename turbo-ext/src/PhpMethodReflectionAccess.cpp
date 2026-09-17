/*
 * Native readers of PHPStan\Reflection\Php\PhpMethodReflection's answers.
 *
 * The method reflection of every userland method stays PHP (its variants and
 * parameters build ExtendedFunctionVariant / PhpParameterReflection, ported
 * separately); the getters the engine asks per method call answer from its
 * promoted slots, its $name / $variants / $returnType memos and the
 * BetterReflection adapter readers of BetterReflectionAccess.cpp, exactly as
 * the methods would:
 *
 * - only an object of exactly the final class (resolved through the class map
 *   without autoloading) takes the readers, its property offsets resolved
 *   once per request;
 * - getName() fills the $name memo as the method does (on PHP 8 both of its
 *   branches memoize the adapter's name);
 * - a getter whose answer needs an unfilled memo other than $name
 *   (getVariants(), getOnlyVariant(), hasSideEffects() before
 *   getReturnType() ran), the adapter's deprecation, the prototype or the
 *   attributes' names calls the method; so does an uninitialized slot, whose
 *   Error stays the twin's.
 *
 * pt_extended_method_reflection_call() (ResolvedMethodReflection.cpp) asks
 * these readers first for such an object.
 */

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"

namespace {

enum : uint32_t
{
	PT_PMR_NAME = 0,
	PT_PMR_VARIANTS,
	PT_PMR_RETURN_TYPE,
	PT_PMR_DECLARING_CLASS,
	PT_PMR_REFLECTION,
	PT_PMR_PHP_DOC_THROW_TYPE,
	PT_PMR_RESOLVED_PHP_DOC_BLOCK,
	PT_PMR_DEPRECATED_DESCRIPTION,
	PT_PMR_IS_DEPRECATED,
	PT_PMR_IS_INTERNAL,
	PT_PMR_IS_FINAL,
	PT_PMR_IS_PURE,
	PT_PMR_ASSERTS,
	PT_PMR_ACCEPTS_NAMED_ARGUMENTS,
	PT_PMR_SELF_OUT_TYPE,
	PT_PMR_PHP_DOC_COMMENT,
	PT_PMR_ATTRIBUTES,
	PT_PMR_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS,
	PT_PMR_SLOT_COUNT,
};

const char *const pt_pmr_names[PT_PMR_SLOT_COUNT] = {
	"name", "variants", "returnType", "declaringClass", "reflection", "phpDocThrowType", "resolvedPhpDocBlock",
	"deprecatedDescription", "isDeprecated", "isInternal", "isFinal", "isPure", "asserts", "acceptsNamedArguments",
	"selfOutType", "phpDocComment", "attributes", "pureUnlessCallableIsImpureParameters",
};

struct Layout
{
	zend_class_entry *ce;
	uint32_t generation;
	uint32_t classCount; /* EG(class_table) size when the class was not declared yet */
	bool usable;         /* false: a class the readers do not know (or no class map) */
	uint32_t offsets[PT_PMR_SLOT_COUNT];
};

Layout pt_pmr_layout = { NULL, 0, 0, false, {} };

zend_never_inline bool resolveLayout(zend_class_entry *candidate)
{
	Layout &layout = pt_pmr_layout;
	uint32_t classCount = zend_hash_num_elements(EG(class_table));
	if (layout.generation == pt_engine_generation) {
		if (layout.ce != NULL) return layout.ce == candidate;
		/* an undeclared class is retried once more classes exist */
		if (!layout.usable || layout.classCount == classCount) return false;
	}
	layout.generation = pt_engine_generation;
	layout.classCount = classCount;
	layout.ce = NULL;
	layout.usable = true;
	zend_class_entry *ce = pt_class_loaded(PT_CLASS_PHP_METHOD_REFLECTION);
	if (ce == NULL) {
		if (UNEXPECTED(EG(exception) != NULL)) layout.usable = false;
		return false;
	}
	for (uint32_t i = 0; i < PT_PMR_SLOT_COUNT; i++) {
		int32_t offset = pt_instance_prop_offset(ce, pt_pmr_names[i], strlen(pt_pmr_names[i]));
		if (UNEXPECTED(offset < 0)) {
			/* not the twin these readers know: every call takes the methods */
			layout.usable = false;
			return false;
		}
		layout.offsets[i] = (uint32_t) offset;
	}
	layout.ce = ce;
	return ce == candidate;
}

inline bool isPhpMethodReflection(zend_class_entry *ce)
{
	const Layout &layout = pt_pmr_layout;
	if (EXPECTED(layout.ce == ce && layout.generation == pt_engine_generation && ce != NULL)) return true;
	return resolveLayout(ce);
}

/* the initialized slot, NULL when never written */
inline zval *slotOf(zend_object *method, uint32_t index)
{
	zval *value = OBJ_PROP(method, pt_pmr_layout.offsets[index]);
	return EXPECTED(Z_TYPE_P(value) != IS_UNDEF) ? value : NULL;
}

inline zv::Val trinary(bool value)
{
	return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value ? PT_TRI_YES : PT_TRI_NO)));
}

enum Answer
{
	PT_PMR_EXCEPTION = -1,
	PT_PMR_UNANSWERED = 0,
	PT_PMR_ANSWERED = 1,
};

/* a slot's value as the answer, unanswered while uninitialized */
inline Answer slotAnswer(zend_object *method, uint32_t index, zv::Val &out)
{
	zval *value = slotOf(method, index);
	if (UNEXPECTED(value == NULL)) return PT_PMR_UNANSWERED;
	out = zv::Val::copyOf(zv::Ref(value));
	return PT_PMR_ANSWERED;
}

/* a bool answer of the adapter reader over $this->reflection */
inline Answer adapterBool(zend_object *method, bool (*reader)(zval *, bool &), bool &out)
{
	zval *reflection = slotOf(method, PT_PMR_REFLECTION);
	if (UNEXPECTED(reflection == NULL)) return PT_PMR_UNANSWERED;
	return reader(reflection, out) ? PT_PMR_ANSWERED : PT_PMR_EXCEPTION;
}

/* getName(): the $name memo, or the adapter's name memoized */
Answer name(zend_object *method, zv::Val &out)
{
	zval *memo = slotOf(method, PT_PMR_NAME);
	if (UNEXPECTED(memo == NULL)) return PT_PMR_UNANSWERED;
	if (EXPECTED(Z_TYPE_P(memo) == IS_STRING)) {
		out = zv::Val::copyOf(zv::Ref(memo));
		return PT_PMR_ANSWERED;
	}
	zval *reflection = slotOf(method, PT_PMR_REFLECTION);
	if (UNEXPECTED(reflection == NULL)) return PT_PMR_UNANSWERED;
	/* $name = $this->reflection->getName(); ... return $this->name = $name; — the
	 * lowercase comparison only guards the PHP < 8 trait-alias fix-up */
	zv::Val computed = pt_method_adapter_get_name(reflection);
	if (UNEXPECTED(computed.isUndef())) return PT_PMR_EXCEPTION;
	if (UNEXPECTED(Z_TYPE_P(computed.raw()) != IS_STRING)) return PT_PMR_UNANSWERED;
	zval previous;
	ZVAL_COPY_VALUE(&previous, memo);
	ZVAL_COPY(memo, computed.raw());
	zval_ptr_dtor(&previous);
	out = std::move(computed);
	return PT_PMR_ANSWERED;
}

/* hasSideEffects() while $returnType is memoized */
Answer hasSideEffects(zend_object *method, zv::Val &out)
{
	zval *returnType = slotOf(method, PT_PMR_RETURN_TYPE);
	if (returnType == NULL || Z_TYPE_P(returnType) != IS_OBJECT) return PT_PMR_UNANSWERED;

	/* strtolower($this->getName()) !== '__construct' && $this->getReturnType()->isVoid()->yes() */
	zv::Val methodName;
	Answer named = name(method, methodName);
	if (named != PT_PMR_ANSWERED) return named;
	if (!zend_string_equals_literal_ci(Z_STR_P(methodName.raw()), "__construct")) {
		zend_long isVoid = pt_type_op_trinary(Z_OBJ_P(returnType), PT_OP_IS_VOID, 0, NULL);
		if (UNEXPECTED(isVoid < 0)) return PT_PMR_EXCEPTION;
		if (isVoid == PT_TRI_YES) {
			out = trinary(true);
			return PT_PMR_ANSWERED;
		}
	}

	/* if ($this->isPure !== null) return TrinaryLogic::createFromBoolean(!$this->isPure); */
	zval *isPure = slotOf(method, PT_PMR_IS_PURE);
	if (UNEXPECTED(isPure == NULL)) return PT_PMR_UNANSWERED;
	if (Z_TYPE_P(isPure) != IS_NULL) {
		out = trinary(Z_TYPE_P(isPure) != IS_TRUE);
		return PT_PMR_ANSWERED;
	}

	/* (new ThisType($this->declaringClass))->isSuperTypeOf($this->getReturnType())->yes() */
	zval *declaringClass = slotOf(method, PT_PMR_DECLARING_CLASS);
	if (UNEXPECTED(declaringClass == NULL)) return PT_PMR_UNANSWERED;
	zval thisTypeZv;
	if (UNEXPECTED(!pt_this_type_new(&thisTypeZv, declaringClass))) return PT_PMR_EXCEPTION;
	zv::Val thisType = zv::Val::adopt(thisTypeZv);
	zv::Val result = pt_type_op(Z_OBJ_P(thisType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, returnType);
	if (UNEXPECTED(result.isUndef())) return PT_PMR_EXCEPTION;
	zend_long yes = pt_type_result_trinary(result.raw());
	if (UNEXPECTED(yes < 0)) return PT_PMR_EXCEPTION;
	zval *maybe = pt_trinary_singleton(yes == PT_TRI_YES ? PT_TRI_YES : PT_TRI_MAYBE);
	out = zv::Val::copyOf(zv::Ref(maybe));
	return PT_PMR_ANSWERED;
}

/* getPureUnlessCallableIsImpureParameters(): array_map(static fn (bool $value) =>
 * TrinaryLogic::createFromBoolean($value), $this->pureUnlessCallableIsImpureParameters) */
Answer pureUnlessCallableIsImpureParameters(zend_object *method, zv::Val &out)
{
	zval *parameters = slotOf(method, PT_PMR_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS);
	if (UNEXPECTED(parameters == NULL || Z_TYPE_P(parameters) != IS_ARRAY)) return PT_PMR_UNANSWERED;
	for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
		zval *value = entry.value().deref().raw();
		if (UNEXPECTED(Z_TYPE_P(value) != IS_TRUE && Z_TYPE_P(value) != IS_FALSE)) return PT_PMR_UNANSWERED;
	}
	zv::Arr mapped = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(parameters)));
	for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
		zv::Val answer = trinary(Z_TYPE_P(entry.value().deref().raw()) == IS_TRUE);
		zend_string *key = entry.stringKeyOrNull();
		if (key != NULL) {
			mapped.set(key, std::move(answer));
		} else {
			mapped.arrRef().setIndex(entry.indexKey(), answer.ref());
		}
	}
	out = zv::Val(std::move(mapped));
	return PT_PMR_ANSWERED;
}

} // namespace

bool pt_is_php_method_reflection(zend_class_entry *ce)
{
	return isPhpMethodReflection(ce);
}

int pt_php_method_reflection_answer(zend_object *method, pt_method_reflection_member member, zv::Val &out)
{
	if (UNEXPECTED(!isPhpMethodReflection(method->ce))) return PT_PMR_UNANSWERED;
	bool flag;
	Answer answer;
	switch (member) {
		case PT_MR_GET_NAME:
			return name(method, out);
		case PT_MR_GET_DECLARING_CLASS:
			return slotAnswer(method, PT_PMR_DECLARING_CLASS, out);
		case PT_MR_IS_STATIC:
			answer = adapterBool(method, pt_method_adapter_is_static, flag);
			if (answer == PT_PMR_ANSWERED) out = zv::Val::boolean(flag);
			return answer;
		case PT_MR_IS_PRIVATE:
			answer = adapterBool(method, pt_method_adapter_is_private, flag);
			if (answer == PT_PMR_ANSWERED) out = zv::Val::boolean(flag);
			return answer;
		case PT_MR_IS_PUBLIC:
			answer = adapterBool(method, pt_method_adapter_is_public, flag);
			if (answer == PT_PMR_ANSWERED) out = zv::Val::boolean(flag);
			return answer;
		case PT_MR_GET_DOC_COMMENT:
			return slotAnswer(method, PT_PMR_PHP_DOC_COMMENT, out);
		case PT_MR_GET_VARIANTS: {
			/* $this->variants ??= [...] — the filled memo */
			zval *variants = slotOf(method, PT_PMR_VARIANTS);
			if (variants == NULL || Z_TYPE_P(variants) != IS_ARRAY) return PT_PMR_UNANSWERED;
			out = zv::Val::copyOf(zv::Ref(variants));
			return PT_PMR_ANSWERED;
		}
		case PT_MR_GET_ONLY_VARIANT: {
			/* $this->getVariants()[0] over the filled memo */
			zval *variants = slotOf(method, PT_PMR_VARIANTS);
			if (variants == NULL || Z_TYPE_P(variants) != IS_ARRAY) return PT_PMR_UNANSWERED;
			zval *variant = zend_hash_index_find(Z_ARRVAL_P(variants), 0);
			if (variant == NULL || Z_TYPE_P(variant) != IS_OBJECT) return PT_PMR_UNANSWERED;
			out = zv::Val::copyOf(zv::Ref(variant));
			return PT_PMR_ANSWERED;
		}
		case PT_MR_GET_NAMED_ARGUMENTS_VARIANTS:
			out = zv::Val::null();
			return PT_PMR_ANSWERED;
		case PT_MR_IS_DEPRECATED: {
			/* if ($this->isDeprecated) return TrinaryLogic::createYes(); — the adapter's
			 * deprecation otherwise, which the method asks */
			zval *isDeprecated = slotOf(method, PT_PMR_IS_DEPRECATED);
			if (isDeprecated == NULL || Z_TYPE_P(isDeprecated) != IS_TRUE) return PT_PMR_UNANSWERED;
			out = trinary(true);
			return PT_PMR_ANSWERED;
		}
		case PT_MR_GET_DEPRECATED_DESCRIPTION: {
			zval *isDeprecated = slotOf(method, PT_PMR_IS_DEPRECATED);
			if (isDeprecated == NULL || Z_TYPE_P(isDeprecated) != IS_TRUE) return PT_PMR_UNANSWERED;
			return slotAnswer(method, PT_PMR_DEPRECATED_DESCRIPTION, out);
		}
		case PT_MR_IS_FINAL: {
			/* TrinaryLogic::createFromBoolean($this->isFinal || $this->reflection->isFinal()) */
			zval *isFinal = slotOf(method, PT_PMR_IS_FINAL);
			if (UNEXPECTED(isFinal == NULL)) return PT_PMR_UNANSWERED;
			if (Z_TYPE_P(isFinal) == IS_TRUE) {
				out = trinary(true);
				return PT_PMR_ANSWERED;
			}
			answer = adapterBool(method, pt_method_adapter_is_final, flag);
			if (answer == PT_PMR_ANSWERED) out = trinary(flag);
			return answer;
		}
		case PT_MR_IS_FINAL_BY_KEYWORD:
			answer = adapterBool(method, pt_method_adapter_is_final, flag);
			if (answer == PT_PMR_ANSWERED) out = trinary(flag);
			return answer;
		case PT_MR_IS_INTERNAL: {
			zval *isInternal = slotOf(method, PT_PMR_IS_INTERNAL);
			if (UNEXPECTED(isInternal == NULL)) return PT_PMR_UNANSWERED;
			out = trinary(Z_TYPE_P(isInternal) == IS_TRUE);
			return PT_PMR_ANSWERED;
		}
		case PT_MR_IS_BUILTIN:
			answer = adapterBool(method, pt_method_adapter_is_internal, flag);
			if (answer == PT_PMR_ANSWERED) out = trinary(flag);
			return answer;
		case PT_MR_GET_THROW_TYPE:
			return slotAnswer(method, PT_PMR_PHP_DOC_THROW_TYPE, out);
		case PT_MR_HAS_SIDE_EFFECTS:
			return hasSideEffects(method, out);
		case PT_MR_IS_PURE: {
			/* $this->isPure === null ? createMaybe() : createFromBoolean($this->isPure) */
			zval *isPure = slotOf(method, PT_PMR_IS_PURE);
			if (UNEXPECTED(isPure == NULL)) return PT_PMR_UNANSWERED;
			out = Z_TYPE_P(isPure) == IS_NULL ? zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_MAYBE))) : trinary(Z_TYPE_P(isPure) == IS_TRUE);
			return PT_PMR_ANSWERED;
		}
		case PT_MR_GET_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS:
			return pureUnlessCallableIsImpureParameters(method, out);
		case PT_MR_GET_ASSERTS:
			return slotAnswer(method, PT_PMR_ASSERTS, out);
		case PT_MR_ACCEPTS_NAMED_ARGUMENTS: {
			/* TrinaryLogic::createFromBoolean($this->declaringClass->acceptsNamedArguments() && $this->acceptsNamedArguments) */
			zval *declaringClass = slotOf(method, PT_PMR_DECLARING_CLASS);
			zval *accepts = slotOf(method, PT_PMR_ACCEPTS_NAMED_ARGUMENTS);
			if (UNEXPECTED(declaringClass == NULL || accepts == NULL || Z_TYPE_P(declaringClass) != IS_OBJECT)) return PT_PMR_UNANSWERED;
			if (UNEXPECTED(!pt_class_reflection_accepts_named_arguments(Z_OBJ_P(declaringClass), flag))) return PT_PMR_EXCEPTION;
			out = trinary(flag && Z_TYPE_P(accepts) == IS_TRUE);
			return PT_PMR_ANSWERED;
		}
		case PT_MR_GET_SELF_OUT_TYPE:
			return slotAnswer(method, PT_PMR_SELF_OUT_TYPE, out);
		case PT_MR_RETURNS_BY_REFERENCE:
			answer = adapterBool(method, pt_method_adapter_returns_reference, flag);
			if (answer == PT_PMR_ANSWERED) out = trinary(flag);
			return answer;
		case PT_MR_IS_ABSTRACT:
			answer = adapterBool(method, pt_method_adapter_is_abstract, flag);
			if (answer == PT_PMR_ANSWERED) out = zv::Val::boolean(flag);
			return answer;
		case PT_MR_GET_ATTRIBUTES:
			return slotAnswer(method, PT_PMR_ATTRIBUTES, out);
		case PT_MR_GET_RESOLVED_PHP_DOC:
			return slotAnswer(method, PT_PMR_RESOLVED_PHP_DOC_BLOCK, out);
		case PT_MR_GET_PROTOTYPE:
		case PT_MR_MUST_USE_RETURN_VALUE:
		case PT_MR_MEMBER_COUNT:
			break;
	}
	return PT_PMR_UNANSWERED;
}
