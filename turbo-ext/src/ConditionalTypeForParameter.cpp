/*
 * PHPStanTurbo\ConditionalTypeForParameter — native implementation of
 * PHPStan\Type\ConditionalTypeForParameter.
 *
 * State is the twin's three class-body properties in slots 0-2 (the
 * template type the parameter narrows and the two normalized-branch memos)
 * and its five promoted constructor properties in slots 3-7;
 * LateResolvableTypeTrait's `private ?Type $result` follows them, declared
 * by the shared registrar in TypeTraits.cpp that also supplies the trait's
 * forwards (the class body's own isSuperTypeOf() wins over the trait's, as
 * in PHP); NonGeneralizableTypeTrait's generalize() comes from its
 * registrar.
 *
 * The class is final, so the trait's isSuperTypeOfDefault() is a direct C++
 * call; toConditional() instantiates the shadowing ConditionalType. Another
 * instance's private slots are read directly, as the twin does from inside
 * the class. NarrowedSubjectType::narrowReferences() stays PHP.
 */

#include "TypeTraits.h"
#include "generated/ConditionalTypeForParameter.h"

namespace slots = ptdecl::ConditionalTypeForParameter::slot;
namespace sigs = ptdecl::ConditionalTypeForParameter::sig;

zend_class_entry *pt_ce_conditional_type_for_parameter = nullptr;

/* the twin's closure names, for the engine's messages */
#define PT_CTFP_CLASS "PHPStan\\Type\\ConditionalTypeForParameter"
#if PHP_VERSION_ID >= 80400
#define PT_CTFP_CLOSURE(method, line) PT_CTFP_CLASS "::{closure:" PT_CTFP_CLASS "::" method "():" line "}"
#else
/* PHP 8.3 names a closure by its namespace alone */
#define PT_CTFP_CLOSURE(method, line) PT_CTFP_CLASS "::PHPStan\\Type\\{closure}"
#endif


namespace phpstanturbo {

/* Mirrors PHPStan\Type\ConditionalTypeForParameter. State lives in the PHP
 * object's slots. */
class ConditionalTypeForParameter
{
public:
	explicit ConditionalTypeForParameter(zend_object *self) : self(self) {}

	/* __construct(private string $parameterName, private Type $target,
	 * private Type $if, private Type $else, private bool $negated); the
	 * name and types borrowed */
	void construct(zend_string *parameterName, zval *target, zval *ifType, zval *elseType, bool negated)
	{
		zval name;
		ZVAL_STR(&name, parameterName);
		writeSlot(slots::parameterName, &name);
		writeSlot(slots::target, target);
		writeSlot(slots::if_, ifType);
		writeSlot(slots::else_, elseType);
		zval negatedZv = {};
		ZVAL_BOOL(&negatedZv, negated);
		writeSlot(slots::negated, &negatedZv);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zend_string *parameterName, zval *target, zval *ifType, zval *elseType, bool negated)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_conditional_type_for_parameter) != SUCCESS)) return zv::Val();
		ConditionalTypeForParameter(Z_OBJ(object)).construct(parameterName, target, ifType, elseType, negated);
		return zv::Val::adopt(object);
	}

	/* the slots (borrowed); NULL with an Error pending when the constructor
	 * never ran */
	[[nodiscard]] zval *parameterName() const { return slot(self, slots::parameterName, "parameterName"); }
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

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_conditional_type_for_parameter, name); }

	zv::Val getParameterName() const { return copyOfSlot(parameterName()); }
	zv::Val getTarget() const { return copyOfSlot(target()); }
	zv::Val getIf() const { return copyOfSlot(ifType()); }
	zv::Val getElse() const { return copyOfSlot(elseType()); }

	/* $type = new self($parameterName, $this->target, $this->if, $this->else, $this->negated);
	 * $type->parameterTemplateType = $this->parameterTemplateType; */
	zv::Val changeParameterName(zend_string *newParameterName) const
	{
		zval *t = target();
		zval *i = t != NULL ? ifType() : NULL;
		zval *e = i != NULL ? elseType() : NULL;
		bool isNegated;
		if (UNEXPECTED(e == NULL || !negated(isNegated))) return zv::Val();
		zv::Val type = create(newParameterName, t, i, e, isNegated);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		pt_write_slot(Z_OBJ_P(type.raw()), slots::parameterTemplateType, OBJ_PROP_NUM(self, slots::parameterTemplateType));
		return type;
	}

	/* Narrows the references to the template type the parameter is declared
	 * with along with the parameter: `@param T $param` makes `($param is X ?
	 * A : B)` narrow T to `T & X` in A and to `T ~ X` in B. Only sound when
	 * nothing but the parameter binds T. */
	zv::Val narrowTemplateType(zval *templateType) const
	{
		zval *name = parameterName();
		zval *t = name != NULL ? target() : NULL;
		zval *i = t != NULL ? ifType() : NULL;
		zval *e = i != NULL ? elseType() : NULL;
		bool isNegated;
		if (UNEXPECTED(e == NULL || !negated(isNegated))) return zv::Val();
		zv::Val type = create(Z_STR_P(name), t, i, e, isNegated);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		pt_write_slot(Z_OBJ_P(type.raw()), slots::parameterTemplateType, templateType);
		return type;
	}

	/* new ConditionalType($subject, $this->target, $this->getNormalizedIf(),
	 * $this->getNormalizedElse(), $this->negated) */
	zv::Val toConditional(zval *subject) const
	{
		zval *t = target();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zv::Val normalizedIf = getNormalizedIf();
		if (UNEXPECTED(normalizedIf.isUndef())) return zv::Val();
		zv::Val normalizedElse = getNormalizedElse();
		if (UNEXPECTED(normalizedElse.isUndef())) return zv::Val();
		bool isNegated;
		if (UNEXPECTED(!negated(isNegated))) return zv::Val();
		zval raw;
		if (UNEXPECTED(!pt_conditional_type_new(&raw, subject, t, normalizedIf.raw(), normalizedElse.raw(), isNegated))) return zv::Val();
		return zv::Val::adopt(raw);
	}

	/* $this->normalizedIf ??= $this->parameterTemplateType === null ? $this->if
	 * : NarrowedSubjectType::narrowReferences($this->if, $this->parameterTemplateType,
	 * $this->target, !$this->negated) */
	zv::Val getNormalizedIf() const { return normalized(slots::normalizedIf, slots::if_, "if", true); }

	/* the same for the else branch, narrowed where the condition does not hold */
	zv::Val getNormalizedElse() const { return normalized(slots::normalizedElse, slots::else_, "else", false); }

	/* Replaces every ConditionalTypeForParameter inside $type with the
	 * ConditionalType on the subject its parameter resolves to - from
	 * $passedArgs when given (the `$this->passedArgs[$name] ?? null` lookup of
	 * ResolvedFunctionVariantWithOriginal), else by calling $getSubjectType
	 * with the parameter name; a null subject leaves that conditional
	 * unresolved. UNDEF = pending exception */
	static zv::Val resolveInType(zval *type, zval *getSubjectType, zval *passedArgs)
	{
		zv::Val has = pt_type_op(Z_OBJ_P(type), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
		if (UNEXPECTED(has.isUndef())) return zv::Val();
		if (!zend_is_true(has.raw())) return zv::Val::copyOf(zv::Ref(type));
		zv::Val callback = pt_type_native_callback(resolveInTypeCallback, getSubjectType, passedArgs);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		return pt_type_traverser_map_of(type, callback.raw());
	}

	/* for another conditional-for-parameter the if branches' answer
	 * combined with the else branches', the trait's default otherwise;
	 * UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_conditional_type_for_parameter)) {
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
		return pt_type_late_resolvable_is_super_type_of_default(self, pt_ce_conditional_type_for_parameter, type);
	}

	/* array_merge() of the target's, if's and else's getReferencedClasses() */
	zv::Val getReferencedClasses() const { return mergedOfParts(PT_LC("getreferencedclasses"), 0, NULL); }

	/* array_merge() of the target's, if's and else's getReferencedTemplateTypes($positionVariance) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const { return mergedOfParts(PT_LC("getreferencedtemplatetypes"), 1, positionVariance); }

	/* $type instanceof self with the same parameter name and equal target,
	 * if and else (the negation is not compared, as the twin does not);
	 * false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_conditional_type_for_parameter)) {
			out = false;
			return true;
		}
		zval *name = parameterName();
		if (UNEXPECTED(name == NULL)) return false;
		zval *theirName = slot(Z_OBJ_P(type), slots::parameterName, "parameterName");
		if (UNEXPECTED(theirName == NULL)) return false;
		if (!zend_string_equals(Z_STR_P(name), Z_STR_P(theirName))) {
			out = false;
			return true;
		}
		static const uint32_t parts[3] = { slots::target, slots::if_, slots::else_ };
		static const char *const names[3] = { "target", "if", "else" };
		for (int i = 0; i < 3; i++) {
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

	/* sprintf('(%s %s %s ? %s : %s)', $parameterName, 'is'/'is not', target, if, else) */
	zv::Val describe(zval *level) const
	{
		zval *name = parameterName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		bool isNegated;
		if (UNEXPECTED(!negated(isNegated))) return zv::Val();
		zv::Val target = describedSlot(slots::target, "target", level);
		if (UNEXPECTED(target.isUndef())) return zv::Val();
		zv::Val ifDescription = describedSlot(slots::if_, "if", level);
		if (UNEXPECTED(ifDescription.isUndef())) return zv::Val();
		zv::Val elseDescription = describedSlot(slots::else_, "else", level);
		if (UNEXPECTED(elseDescription.isUndef())) return zv::Val();
		return zv::Val::adoptString(zend_strpprintf(0, "(%s %s %s ? %s : %s)", ZSTR_VAL(Z_STR_P(name)), isNegated ? "is not" : "is", ZSTR_VAL(Z_STR_P(target.raw())), ZSTR_VAL(Z_STR_P(ifDescription.raw())), ZSTR_VAL(Z_STR_P(elseDescription.raw()))));
	}

	/* TypeCombinator::union($this->getNormalizedIf(), $this->getNormalizedElse()) */
	zv::Val getResult() const
	{
		zv::Val normalizedIf = getNormalizedIf();
		if (UNEXPECTED(normalizedIf.isUndef())) return zv::Val();
		zv::Val normalizedElse = getNormalizedElse();
		if (UNEXPECTED(normalizedElse.isUndef())) return zv::Val();
		zv::Args args{normalizedIf.raw(), normalizedElse.raw()};
		return pt_type_combinator_call(PT_LC("union"), 2, args);
	}

	/* new self() over the callback's results for the target, if and else
	 * when it changed any, $this otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		return traverseWith(NULL, fci, fcc);
	}

	/* $this for a $right of another class, else traverse() with $right's
	 * parts as the callback's second arguments */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_conditional_type_for_parameter)) return thisValue();
		return traverseWith(Z_OBJ_P(right), fci, fcc);
	}

	/* new ConditionalTypeForParameterNode($this->parameterName, <the three
	 * parts' nodes>, $this->negated) */
	zv::Val toPhpDocNode() const
	{
		zval *name = parameterName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval args[5];
		ZVAL_COPY_VALUE(&args[0], name);
		zv::Val nodes[3];
		static const uint32_t parts[3] = { slots::target, slots::if_, slots::else_ };
		static const char *const names[3] = { "target", "if", "else" };
		for (int i = 0; i < 3; i++) {
			zval *part = slot(self, parts[i], names[i]);
			if (UNEXPECTED(part == NULL)) return zv::Val();
			nodes[i] = pt_type_call(Z_OBJ_P(part), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(nodes[i].isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&args[i + 1], nodes[i].raw());
		}
		bool isNegated;
		if (UNEXPECTED(!negated(isNegated))) return zv::Val();
		ZVAL_BOOL(&args[4], isNegated);
		return pt_type_new(PT_CLASS_CONDITIONAL_TYPE_FOR_PARAMETER_NODE, 5, args);
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	/* NarrowedSubjectType::narrowReferences($branch, $subject, $target, $conditionHolds) — stays PHP */
	static zv::Val narrowReferences(zval *args)
	{
		zend_class_entry *narrowedSubjectType = pt_class(PT_CLASS_NARROWED_SUBJECT_TYPE);
		if (UNEXPECTED(narrowedSubjectType == NULL)) return zv::Val();
		return pt_type_call_static_ce(narrowedSubjectType, PT_LC("narrowreferences"), 4, args);
	}

	zv::Val normalized(uint32_t memoIndex, uint32_t branchIndex, const char *branchName, bool isIf) const
	{
		zval *memo = OBJ_PROP_NUM(self, memoIndex);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zval *parameterTemplateType = OBJ_PROP_NUM(self, slots::parameterTemplateType);
		zv::Val computed;
		if (Z_TYPE_P(parameterTemplateType) != IS_OBJECT) {
			zval *branch = slot(self, branchIndex, branchName);
			if (UNEXPECTED(branch == NULL)) return zv::Val();
			computed = zv::Val::copyOf(zv::Ref(branch));
		} else {
			zval *branch = slot(self, branchIndex, branchName);
			zval *t = branch != NULL ? target() : NULL;
			if (UNEXPECTED(t == NULL)) return zv::Val();
			bool isNegated;
			if (UNEXPECTED(!negated(isNegated))) return zv::Val();
			/* the if branch knows the condition holds unless negated; the else
			 * branch knows it holds exactly when negated */
			zv::Args args{branch, parameterTemplateType, t, isIf ? !isNegated : isNegated};
			computed = narrowReferences(args);
			if (UNEXPECTED(computed.isUndef())) return zv::Val();
		}
		zv::Ref(memo).assign(zv::Val::copyOf(zv::Ref(computed.raw())));
		return computed;
	}

	/* the `static function (Type $type, callable $traverse) use ($getSubjectType)`
	 * of resolveInType() — state0 the callable, state1 the passed-arguments
	 * table that stands in for it (NULL = call the callable) */
	static void resolveInTypeCallback(zval *getSubjectType, zval *passedArgs, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function " PT_CTFP_CLOSURE("resolveInType", "116") "(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *type = &argv[0];
		zval *traverse = &argv[1];
		if (Z_TYPE_P(type) == IS_OBJECT && instanceof_function(Z_OBJCE_P(type), pt_ce_conditional_type_for_parameter)) {
			zval *name = slot(Z_OBJ_P(type), slots::parameterName, "parameterName");
			if (UNEXPECTED(name == NULL)) return;
			zv::Val subjectType;
			if (passedArgs != NULL && Z_TYPE_P(passedArgs) == IS_ARRAY) {
				/* $this->passedArgs[$parameterName] ?? null */
				zval *found = zend_symtable_find(Z_ARRVAL_P(passedArgs), Z_STR_P(name));
				subjectType = found != NULL ? zv::Val::copyOf(zv::Ref(found).deref()) : zv::Val::null();
			} else {
				subjectType = pt_type_call_callable(getSubjectType, 1, name);
				if (UNEXPECTED(subjectType.isUndef())) return;
			}
			if (!subjectType.isNull()) {
				/* traverse children first, then convert — avoids an infinite loop
				 * when the subject contains a ConditionalTypeForParameter with a
				 * colliding parameter name */
				zval traversed;
				if (UNEXPECTED(!pt_type_traverser_traverse(&traversed, traverse, type))) return;
				zv::Val traversedHold = zv::Val::adopt(traversed);
				if (Z_TYPE(traversed) == IS_OBJECT && instanceof_function(Z_OBJCE(traversed), pt_ce_conditional_type_for_parameter)) {
					zv::Val conditional = ConditionalTypeForParameter(Z_OBJ(traversed)).toConditional(subjectType.raw());
					if (UNEXPECTED(conditional.isUndef())) return;
					conditional.intoReturnValue(return_value);
					return;
				}
				traversedHold.intoReturnValue(return_value);
				return;
			}
		}
		(void) pt_type_traverser_traverse(return_value, traverse, type);
	}

	static zv::Val copyOfSlot(zval *p)
	{
		if (UNEXPECTED(p == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(p));
	}

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

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

	/* array_merge() of the target's, if's and else's method(...$args);
	 * UNDEF = pending exception */
	zv::Val mergedOfParts(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		static const uint32_t parts[3] = { slots::target, slots::if_, slots::else_ };
		static const char *const names[3] = { "target", "if", "else" };
		zv::Arr merged = zv::Arr::create(0);
		for (int i = 0; i < 3; i++) {
			zval *part = slot(self, parts[i], names[i]);
			if (UNEXPECTED(part == NULL)) return zv::Val();
			zv::Val ofPart = pt_type_call(Z_OBJ_P(part), lcname, len, argc, argv);
			if (UNEXPECTED(ofPart.isUndef() || !pt_callable_array_merge_into(merged, ofPart.raw()))) return zv::Val();
		}
		return zv::Val(std::move(merged));
	}

	/* the target (0) or the normalized if (1) / else (2) of an instance */
	static zv::Val part(zend_object *object, int index)
	{
		ConditionalTypeForParameter type(object);
		if (index == 0) {
			zval *t = type.target();
			return t != NULL ? zv::Val::copyOf(zv::Ref(t)) : zv::Val();
		}
		return index == 1 ? type.getNormalizedIf() : type.getNormalizedElse();
	}

	/* traverse() / traverseSimultaneously() ($right NULL for the former):
	 * the callback over the target and the normalized branches (with
	 * $right's counterparts as second arguments), a new self when any
	 * changed */
	zv::Val traverseWith(zend_object *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		/* the target, then $this->getNormalizedIf() and getNormalizedElse() */
		zv::Val mine[3];
		zv::Val mapped[3];
		bool stillOriginal = true;
		for (int i = 0; i < 3; i++) {
			mine[i] = part(self, i);
			if (UNEXPECTED(mine[i].isUndef())) return zv::Val();
			zv::Val theirs;
			if (right != NULL) {
				theirs = part(right, i);
				if (UNEXPECTED(theirs.isUndef())) return zv::Val();
			}
			mapped[i] = pt_type_traverse_call(fci, fcc, mine[i].raw(), right != NULL ? theirs.raw() : NULL);
			if (UNEXPECTED(mapped[i].isUndef())) return zv::Val();
			if (!pt_type_same_object(mine[i].raw(), mapped[i].raw())) {
				stillOriginal = false;
			}
		}
		if (stillOriginal) return thisValue();
		zval *name = parameterName();
		bool isNegated;
		if (UNEXPECTED(name == NULL || !negated(isNegated))) return zv::Val();
		return create(Z_STR_P(name), mapped[0].raw(), mapped[1].raw(), mapped[2].raw(), isNegated);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ConditionalTypeForParameter;

/* ConditionalTypeForParameter::resolveInType($type, $getSubjectType) */
zv::Val pt_conditional_type_for_parameter_resolve_in_type(zval *type, zval *getSubjectType)
{
	return ConditionalTypeForParameter::resolveInType(type, getSubjectType, NULL);
}

/* ConditionalTypeForParameter::resolveInType($type, fn ($name) => $passedArgs[$name] ?? null) */
zv::Val pt_conditional_type_for_parameter_resolve_in_type_with_args(zval *type, zval *passedArgs)
{
	return ConditionalTypeForParameter::resolveInType(type, NULL, passedArgs);
}

/* $type->narrowTemplateType($templateType) for a ConditionalTypeForParameter */
zv::Val pt_conditional_type_for_parameter_narrow_template_type(zval *type, zval *templateType)
{
	return ConditionalTypeForParameter(Z_OBJ_P(type)).narrowTemplateType(templateType);
}

bool pt_conditional_type_for_parameter_new(zval *out, zend_string *parameterName, zval *target, zval *ifType, zval *elseType, bool negated)
{
	return pt_val_into(ConditionalTypeForParameter::create(parameterName, target, ifType, elseType, negated), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ConditionalTypeForParameter(Z_OBJ_P(ZEND_THIS))

void pt_register_conditional_type_for_parameter()
{

	reg::Class cls("PHPStan\\Type\\ConditionalTypeForParameter");
	ptdecl::ConditionalTypeForParameter::declareClass(cls);
	/* the slots must stay in this order (PT_CTP_PROP_*); the trait registrar
	 * declares $result after them */
	ptdecl::ConditionalTypeForParameter::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *parameterName;
		zval *target, *ifType, *elseType;
		bool negated;
		if (!zp::parse<zp::Str, zp::Obj, zp::Obj, zp::Obj, zp::Bool>(execute_data, parameterName, target, ifType, elseType, negated)) RETURN_THROWS();
		PT_THIS.construct(parameterName, target, ifType, elseType, negated);
	});

	cls.method<&ConditionalTypeForParameter::getParameterName>(sigs::getParameterName);
	cls.method<&ConditionalTypeForParameter::getTarget>(sigs::getTarget);
	cls.method<&ConditionalTypeForParameter::getIf>(sigs::getIf);
	cls.method<&ConditionalTypeForParameter::getElse>(sigs::getElse);
	cls.method<&ConditionalTypeForParameter::negated>(sigs::isNegated);

	cls.method<&ConditionalTypeForParameter::changeParameterName, zp::Str>(sigs::changeParameterName);

	cls.method<&ConditionalTypeForParameter::narrowTemplateType, zp::Obj>(sigs::narrowTemplateType);

	cls.method(sigs::resolveInType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *getSubjectType;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(type, pt_class(PT_CLASS_TYPE))
			Z_PARAM_ZVAL(getSubjectType)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!zend_is_callable(getSubjectType, 0, NULL))) {
			zend_argument_type_error(2, "must be of type callable, %s given", zend_zval_value_name(getSubjectType));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(ConditionalTypeForParameter::resolveInType(type, getSubjectType, NULL));
	});

	cls.method<&ConditionalTypeForParameter::toConditional, zp::Obj>(sigs::toConditional);

	cls.method<&ConditionalTypeForParameter::getNormalizedIf>(sigs::getNormalizedIf);
	cls.method<&ConditionalTypeForParameter::getNormalizedElse>(sigs::getNormalizedElse);

	cls.method<&ConditionalTypeForParameter::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method<&ConditionalTypeForParameter::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &ConditionalTypeForParameter::getReferencedClasses>();

	cls.method<&ConditionalTypeForParameter::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&ConditionalTypeForParameter::equals, zp::Obj>(sigs::equals);

	cls.method<&ConditionalTypeForParameter::describe, zp::Obj>(sigs::describe);

	cls.method(sigs::isResolvable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	cls.method<&ConditionalTypeForParameter::getResult>(sigs::getResult);

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

	cls.method<&ConditionalTypeForParameter::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::ConditionalTypeForParameter::registerTraits(cls);

	cls.shadow(&pt_ce_conditional_type_for_parameter);
}

/* }}} */
