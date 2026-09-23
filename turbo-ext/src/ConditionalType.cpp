/*
 * PHPStanTurbo\ConditionalType — native implementation of
 * PHPStan\Type\ConditionalType.
 *
 * State is the twin's two `private ?Type` memo properties (slots 0-1, in
 * declaration order) followed by the five promoted constructor properties
 * (slots 2-6); LateResolvableTypeTrait's `private ?Type $result` follows
 * them, declared by the shared registrar in TypeTraits.cpp that also
 * supplies the trait's forwards (the class body's own isSuperTypeOf() wins
 * over the trait's, as in PHP); NonGeneralizableTypeTrait's generalize()
 * comes from its registrar.
 *
 * The class is final, so the private memo getters and the trait's
 * isSuperTypeOfDefault() are direct C++ calls; the normalized branches go
 * through NarrowedSubjectType::narrowReferences(), which stays PHP. Another
 * instance's private slots and memo getters are reached directly, as the
 * twin does from inside the class.
 */

#include "TypeTraits.h"
#include "generated/ConditionalType.h"

namespace slots = ptdecl::ConditionalType::slot;
namespace sigs = ptdecl::ConditionalType::sig;

zend_class_entry *pt_ce_conditional_type = nullptr;


namespace phpstanturbo {

/* Mirrors PHPStan\Type\ConditionalType. State lives in the PHP object's slots. */
class ConditionalType
{
public:
	explicit ConditionalType(zend_object *self) : self(self) {}

	/* __construct(private Type $subject, private Type $target, private Type
	 * $if, private Type $else, private bool $negated); the types borrowed */
	void construct(zval *subject, zval *target, zval *ifType, zval *elseType, bool negated)
	{
		writeSlot(slots::subject, subject);
		writeSlot(slots::target, target);
		writeSlot(slots::if_, ifType);
		writeSlot(slots::else_, elseType);
		zval negatedZv = {};
		ZVAL_BOOL(&negatedZv, negated);
		writeSlot(slots::negated, &negatedZv);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *subject, zval *target, zval *ifType, zval *elseType, bool negated)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_conditional_type) != SUCCESS)) return zv::Val();
		ConditionalType(Z_OBJ(object)).construct(subject, target, ifType, elseType, negated);
		return zv::Val::adopt(object);
	}

	/* the promoted slots (borrowed); NULL with an Error pending when the
	 * constructor never ran */
	[[nodiscard]] zval *subject() const { return slot(self, slots::subject, "subject"); }
	zval *target() const { return slot(self, slots::target, "target"); }
	zval *ifType() const { return slot(self, slots::if_, "if"); }
	zval *elseType() const { return slot(self, slots::else_, "else"); }

	/* $this->negated; false with an Error pending when uninitialized */
	[[nodiscard]] bool negated(bool &out) const
	{
		zval *p = slot(self, slots::negated, "negated");
		if (UNEXPECTED(p == NULL)) return false;
		out = Z_TYPE_P(p) == IS_TRUE;
		return true;
	}

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_conditional_type, name); }

	zv::Val getSubject() const { return copyOfSlot(subject()); }
	zv::Val getTarget() const { return copyOfSlot(target()); }
	zv::Val getIf() const { return copyOfSlot(ifType()); }
	zv::Val getElse() const { return copyOfSlot(elseType()); }

	/* for another conditional the if branches' answer combined with the
	 * else branches', the trait's default otherwise; UNDEF = pending
	 * exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_conditional_type)) {
			zval *i = ifType();
			if (UNEXPECTED(i == NULL)) return zv::Val();
			zval *theirIf = slot(Z_OBJ_P(type), slots::if_, "if");
			if (UNEXPECTED(theirIf == NULL)) return zv::Val();
			zv::Val ifResult = pt_type_op(Z_OBJ_P(i), PT_OP_IS_SUPER_TYPE_OF, 1, theirIf);
			if (UNEXPECTED(ifResult.isUndef())) return zv::Val();
			zval *e = elseType();
			if (UNEXPECTED(e == NULL)) return zv::Val();
			zval *theirElse = slot(Z_OBJ_P(type), slots::else_, "else");
			if (UNEXPECTED(theirElse == NULL)) return zv::Val();
			zv::Val elseResult = pt_type_op(Z_OBJ_P(e), PT_OP_IS_SUPER_TYPE_OF, 1, theirElse);
			if (UNEXPECTED(elseResult.isUndef())) return zv::Val();
			return pt_type_result_and(std::move(ifResult), elseResult.raw());
		}
		return pt_type_late_resolvable_is_super_type_of_default(self, pt_ce_conditional_type, type);
	}

	/* array_merge() of the four parts' getReferencedClasses() */
	zv::Val getReferencedClasses() const { return mergedOfParts(PT_LC("getreferencedclasses"), 0, NULL); }

	/* array_merge() of the four parts' getReferencedTemplateTypes($positionVariance) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const { return mergedOfParts(PT_LC("getreferencedtemplatetypes"), 1, positionVariance); }

	/* $type instanceof self with equal subject, target, if and else (the
	 * negation is not compared, as the twin does not); false with an
	 * exception pending */
	bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_conditional_type)) {
			out = false;
			return true;
		}
		static const uint32_t parts[4] = { slots::subject, slots::target, slots::if_, slots::else_ };
		static const char *const names[4] = { "subject", "target", "if", "else" };
		for (int i = 0; i < 4; i++) {
			zval *mine = slot(self, parts[i], names[i]);
			if (UNEXPECTED(mine == NULL)) return false;
			zval *theirs = slot(Z_OBJ_P(type), parts[i], names[i]);
			if (UNEXPECTED(theirs == NULL)) return false;
			zv::Val equal = pt_type_op(Z_OBJ_P(mine), PT_OP_EQUALS, 1, theirs);
			if (UNEXPECTED(equal.isUndef())) return false;
			if (!zend_is_true(equal.raw())) {
				out = false;
				return true;
			}
		}
		out = true;
		return true;
	}

	/* sprintf('(%s %s %s ? %s : %s)', subject, 'is'/'is not', target, if, else) */
	zv::Val describe(zval *level) const
	{
		zv::Val subject = describedSlot(slots::subject, "subject", level);
		if (UNEXPECTED(subject.isUndef())) return zv::Val();
		bool isNegated;
		if (UNEXPECTED(!negated(isNegated))) return zv::Val();
		zv::Val target = describedSlot(slots::target, "target", level);
		if (UNEXPECTED(target.isUndef())) return zv::Val();
		zv::Val ifDescription = describedSlot(slots::if_, "if", level);
		if (UNEXPECTED(ifDescription.isUndef())) return zv::Val();
		zv::Val elseDescription = describedSlot(slots::else_, "else", level);
		if (UNEXPECTED(elseDescription.isUndef())) return zv::Val();
		return zv::Val::adoptString(zend_strpprintf(0, "(%s %s %s ? %s : %s)", ZSTR_VAL(Z_STR_P(subject.raw())), isNegated ? "is not" : "is", ZSTR_VAL(Z_STR_P(target.raw())), ZSTR_VAL(Z_STR_P(ifDescription.raw())), ZSTR_VAL(Z_STR_P(elseDescription.raw()))));
	}

	/* true without template types in the subject and target, else whether
	 * the target's isSuperTypeOf() the subject is decided; false = pending
	 * exception */
	[[nodiscard]] bool isResolvable(bool &out) const
	{
		zval *s = subject();
		if (UNEXPECTED(s == NULL)) return false;
		bool subjectContains;
		if (UNEXPECTED(!pt_type_utils_contains_template_type(s, subjectContains))) return false;
		if (!subjectContains) {
			zval *t = target();
			if (UNEXPECTED(t == NULL)) return false;
			bool targetContains;
			if (UNEXPECTED(!pt_type_utils_contains_template_type(t, targetContains))) return false;
			if (!targetContains) {
				out = true;
				return true;
			}
		}
		zend_long isSuperType = targetIsSuperTypeOfSubject();
		if (UNEXPECTED(isSuperType < 0)) return false;
		out = isSuperType == PT_TRI_YES || isSuperType == PT_TRI_NO;
		return true;
	}

	/* the normalized if (else when negated) when the target is a supertype
	 * of the subject, the other branch when it is not, the union of both
	 * otherwise; UNDEF = pending exception */
	zv::Val getResult() const
	{
		zend_long isSuperType = targetIsSuperTypeOfSubject();
		if (UNEXPECTED(isSuperType < 0)) return zv::Val();
		bool isNegated;
		if (UNEXPECTED(!negated(isNegated))) return zv::Val();
		if (isSuperType == PT_TRI_YES) return !isNegated ? getNormalizedIf() : getNormalizedElse();
		if (isSuperType == PT_TRI_NO) return !isNegated ? getNormalizedElse() : getNormalizedIf();
		zv::Val normalizedIf = getNormalizedIf();
		if (UNEXPECTED(normalizedIf.isUndef())) return zv::Val();
		zv::Val normalizedElse = getNormalizedElse();
		if (UNEXPECTED(normalizedElse.isUndef())) return zv::Val();
		return combinator2(PT_LC("union"), normalizedIf.raw(), normalizedElse.raw());
	}

	/* new self() over the callback's results for the subject, target and
	 * the normalized branches when it changed any, $this otherwise; UNDEF =
	 * pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		return traverseWith(NULL, fci, fcc);
	}

	/* $this for a $right of another class, else traverse() with $right's
	 * parts as the callback's second arguments */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_conditional_type)) return thisValue();
		return traverseWith(Z_OBJ_P(right), fci, fcc);
	}

	/* new ConditionalTypeNode(<the four parts' nodes>, $this->negated) */
	zv::Val toPhpDocNode() const
	{
		zval args[5];
		zv::Val nodes[4];
		static const uint32_t parts[4] = { slots::subject, slots::target, slots::if_, slots::else_ };
		static const char *const names[4] = { "subject", "target", "if", "else" };
		for (int i = 0; i < 4; i++) {
			zval *part = slot(self, parts[i], names[i]);
			if (UNEXPECTED(part == NULL)) return zv::Val();
			nodes[i] = pt_type_call(Z_OBJ_P(part), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(nodes[i].isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&args[i], nodes[i].raw());
		}
		bool isNegated;
		if (UNEXPECTED(!negated(isNegated))) return zv::Val();
		ZVAL_BOOL(&args[4], isNegated);
		return pt_type_new(PT_CLASS_CONDITIONAL_TYPE_NODE, 5, args);
	}

	/* $this->normalizedIf ??= $this->narrowSubjectIn($this->if, !$this->negated) */
	zv::Val getNormalizedIf() const { return normalized(slots::normalizedIf, slots::if_, "if", true); }

	/* $this->normalizedElse ??= $this->narrowSubjectIn($this->else, $this->negated) */
	zv::Val getNormalizedElse() const { return normalized(slots::normalizedElse, slots::else_, "else", false); }

	/* Replaces the references to the subject in a branch with what the branch
	 * knows about it (NarrowedSubjectType::narrowReferences()). A branch can
	 * only reference the subject while it is a template type; a concrete
	 * subject is never narrowed, so an equal but unrelated type inside the
	 * branch - one the TypeCombinator memo shares an instance with - stays
	 * what it is. */
	zv::Val narrowSubjectIn(zval *branch, bool conditionHolds) const
	{
		zval *s = subject();
		if (UNEXPECTED(s == NULL)) return zv::Val();
		zend_class_entry *templateType = pt_class(PT_CLASS_TEMPLATE_TYPE);
		if (UNEXPECTED(templateType == NULL)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(s), templateType)) return zv::Val::copyOf(zv::Ref(branch));
		zval *t = target();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zv::Args args{branch, s, t, conditionHolds};
		return narrowReferences(args);
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	static zv::Val copyOfSlot(zval *p)
	{
		if (UNEXPECTED(p == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(p));
	}

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* NarrowedSubjectType::narrowReferences($branch, $subject, $target, $conditionHolds) — stays PHP */
	static zv::Val narrowReferences(zval *args)
	{
		zend_class_entry *narrowedSubjectType = pt_class(PT_CLASS_NARROWED_SUBJECT_TYPE);
		if (UNEXPECTED(narrowedSubjectType == NULL)) return zv::Val();
		return pt_type_call_static_ce(narrowedSubjectType, PT_LC("narrowreferences"), 4, args);
	}

	/* TypeCombinator::<method>($a, $b); UNDEF = pending exception */
	static zv::Val combinator2(const char *lcname, size_t len, zval *a, zval *b)
	{
		zv::Args args{a, b};
		return pt_type_combinator_call(lcname, len, 2, args);
	}

	/* $this->target->isSuperTypeOf($this->subject)'s trinary value; -1 =
	 * pending exception */
	zend_long targetIsSuperTypeOfSubject() const
	{
		zval *t = target();
		zval *s = t != NULL ? subject() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(s == NULL)) return -1;
		zv::Val result = pt_type_op(Z_OBJ_P(t), PT_OP_IS_SUPER_TYPE_OF, 1, s);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_result_trinary(result.raw());
	}

	/* $this-><slot>->describe($level), a string; UNDEF = pending exception */
	zv::Val describedSlot(uint32_t index, const char *name, zval *level) const
	{
		zval *part = slot(self, index, name);
		if (UNEXPECTED(part == NULL)) return zv::Val();
		zv::Val description = pt_type_op(Z_OBJ_P(part), PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return a string");
			return zv::Val();
		}
		return description;
	}

	/* array_merge() of the subject's, target's, if's and else's
	 * method(...$args); UNDEF = pending exception */
	zv::Val mergedOfParts(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		static const uint32_t parts[4] = { slots::subject, slots::target, slots::if_, slots::else_ };
		static const char *const names[4] = { "subject", "target", "if", "else" };
		zv::Arr merged = zv::Arr::create(0);
		for (int i = 0; i < 4; i++) {
			zval *part = slot(self, parts[i], names[i]);
			if (UNEXPECTED(part == NULL)) return zv::Val();
			zv::Val ofPart = pt_type_call(Z_OBJ_P(part), lcname, len, argc, argv);
			if (UNEXPECTED(ofPart.isUndef() || !pt_callable_array_merge_into(merged, ofPart.raw()))) return zv::Val();
		}
		return zv::Val(std::move(merged));
	}

	/* the memo slot's value when set, else the computed one stored into it
	 * (`??=`); UNDEF = pending exception */
	zv::Val memoized(uint32_t memoIndex, zv::Val computed) const
	{
		if (UNEXPECTED(computed.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(computed.raw()).isObject())) {
			zend_type_error("phpstan_turbo: expected %s, %s given", ptcls::type, zend_zval_value_name(computed.raw()));
			return zv::Val();
		}
		zv::Ref(OBJ_PROP_NUM(self, memoIndex)).assign(zv::Val::copyOf(zv::Ref(computed.raw())));
		return computed;
	}

	zv::Val normalized(uint32_t memoIndex, uint32_t branchIndex, const char *branchName, bool isIf) const
	{
		zval *memo = OBJ_PROP_NUM(self, memoIndex);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zval *branch = slot(self, branchIndex, branchName);
		if (UNEXPECTED(branch == NULL)) return zv::Val();
		bool isNegated;
		if (UNEXPECTED(!negated(isNegated))) return zv::Val();
		/* the if branch knows the condition holds unless negated; the else
		 * branch knows it holds exactly when negated */
		return memoized(memoIndex, narrowSubjectIn(branch, isIf ? !isNegated : isNegated));
	}

	/* traverse() / traverseSimultaneously() ($right NULL for the former):
	 * the callback over the subject, the target and the normalized branches
	 * (with $right's counterparts as second arguments), a new self when any
	 * changed */
	zv::Val traverseWith(zend_object *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *s = subject();
		if (UNEXPECTED(s == NULL)) return zv::Val();
		zval *rightSubject = right != NULL ? slot(right, slots::subject, "subject") : NULL;
		if (UNEXPECTED(right != NULL && rightSubject == NULL)) return zv::Val();
		zv::Val subject = pt_type_traverse_call(fci, fcc, s, rightSubject);
		if (UNEXPECTED(subject.isUndef())) return zv::Val();
		zval *t = target();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval *rightTarget = right != NULL ? slot(right, slots::target, "target") : NULL;
		if (UNEXPECTED(right != NULL && rightTarget == NULL)) return zv::Val();
		zv::Val target = pt_type_traverse_call(fci, fcc, t, rightTarget);
		if (UNEXPECTED(target.isUndef())) return zv::Val();
		zv::Val normalizedIf = getNormalizedIf();
		if (UNEXPECTED(normalizedIf.isUndef())) return zv::Val();
		zv::Val rightNormalizedIf;
		if (right != NULL) {
			rightNormalizedIf = ConditionalType(right).getNormalizedIf();
			if (UNEXPECTED(rightNormalizedIf.isUndef())) return zv::Val();
		}
		zv::Val ifType = pt_type_traverse_call(fci, fcc, normalizedIf.raw(), right != NULL ? rightNormalizedIf.raw() : NULL);
		if (UNEXPECTED(ifType.isUndef())) return zv::Val();
		zv::Val normalizedElse = getNormalizedElse();
		if (UNEXPECTED(normalizedElse.isUndef())) return zv::Val();
		zv::Val rightNormalizedElse;
		if (right != NULL) {
			rightNormalizedElse = ConditionalType(right).getNormalizedElse();
			if (UNEXPECTED(rightNormalizedElse.isUndef())) return zv::Val();
		}
		zv::Val elseType = pt_type_traverse_call(fci, fcc, normalizedElse.raw(), right != NULL ? rightNormalizedElse.raw() : NULL);
		if (UNEXPECTED(elseType.isUndef())) return zv::Val();
		if (pt_type_same_object(s, subject.raw()) && pt_type_same_object(t, target.raw()) && pt_type_same_object(normalizedIf.raw(), ifType.raw()) && pt_type_same_object(normalizedElse.raw(), elseType.raw())) {
			return thisValue();
		}
		bool isNegated;
		if (UNEXPECTED(!negated(isNegated))) return zv::Val();
		return create(subject.raw(), target.raw(), ifType.raw(), elseType.raw(), isNegated);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ConditionalType;

bool pt_conditional_type_new(zval *out, zval *subject, zval *target, zval *ifType, zval *elseType, bool negated)
{
	return pt_val_into(ConditionalType::create(subject, target, ifType, elseType, negated), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ConditionalType(Z_OBJ_P(ZEND_THIS))

void pt_register_conditional_type()
{
	reg::Class cls("PHPStan\\Type\\ConditionalType");
	ptdecl::ConditionalType::declareClass(cls);
	/* the slots must stay in this order (PT_CT_PROP_*): the memo properties
	 * of the class body, then the promoted ones; the trait registrar
	 * declares $result after them */
	cls.privateTypedClassPropertyDefaultNull("normalizedIf", ptcls::type);
	cls.privateTypedClassPropertyDefaultNull("normalizedElse", ptcls::type);
	cls.privateTypedClassProperty("subject", ptcls::type, false);
	cls.privateTypedClassProperty("target", ptcls::type, false);
	cls.privateTypedClassProperty("if", ptcls::type, false);
	cls.privateTypedClassProperty("else", ptcls::type, false);
	cls.privateTypedProperty("negated", MAY_BE_BOOL);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *subject, *target, *ifType, *elseType;
		bool negated;
		if (!zp::parse<zp::TypeObj, zp::TypeObj, zp::TypeObj, zp::TypeObj, zp::Bool>(execute_data, subject, target, ifType, elseType, negated)) RETURN_THROWS();
		PT_THIS.construct(subject, target, ifType, elseType, negated);
	});

	cls.method<&ConditionalType::getSubject>(sigs::getSubject);
	cls.method<&ConditionalType::getTarget>(sigs::getTarget);
	cls.method<&ConditionalType::getIf>(sigs::getIf);
	cls.method<&ConditionalType::getElse>(sigs::getElse);
	cls.method<&ConditionalType::negated>(sigs::isNegated);

	cls.method<&ConditionalType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method<&ConditionalType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &ConditionalType::getReferencedClasses>();

	cls.method<&ConditionalType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&ConditionalType::equals, zp::TypeObj>(sigs::equals);

	cls.method<&ConditionalType::describe, zp::Obj>(sigs::describe);

	cls.method<&ConditionalType::isResolvable>(sigs::isResolvable);

	cls.method<&ConditionalType::getResult>(sigs::getResult);

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.method<&ConditionalType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&ConditionalType::getNormalizedIf>(sigs::getNormalizedIf);
	cls.method<&ConditionalType::getNormalizedElse>(sigs::getNormalizedElse);
	cls.method<&ConditionalType::narrowSubjectIn, zp::Obj, zp::Bool>(sigs::narrowSubjectIn);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::ConditionalType::registerTraits(cls);

	cls.shadow(&pt_ce_conditional_type);
}

/* }}} */
