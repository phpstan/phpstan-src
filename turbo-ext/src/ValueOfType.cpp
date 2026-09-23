/*
 * PHPStanTurbo\ValueOfType — native implementation of PHPStan\Type\ValueOfType.
 *
 * State is the twin's promoted `private Type $type` in slot 0;
 * LateResolvableTypeTrait's `private ?Type $result` follows it, declared by
 * the shared registrar in TypeTraits.cpp that also supplies the trait's
 * forwards; NonGeneralizableTypeTrait's generalize() comes from its
 * registrar.
 *
 * The twin's own bodies make no $this-calls; another instance's private
 * slot (`$type->type`) is read directly, as the twin does from inside the
 * class.
 */

#include "TypeTraits.h"
#include "generated/ValueOfType.h"

namespace slots = ptdecl::ValueOfType::slot;
namespace sigs = ptdecl::ValueOfType::sig;

zend_class_entry *pt_ce_value_of_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\ValueOfType. State lives in the PHP object's slot. */
class ValueOfType
{
public:
	explicit ValueOfType(zend_object *self) : self(self) {}

	/* __construct(private Type $type); borrowed */
	void construct(zval *type)
	{
		zval *p = OBJ_PROP_NUM(self, slots::type);
		/* the slot is overwritten in place: a repeated __construct() call
		 * would otherwise leak the first value */
		zval previous;
		ZVAL_COPY_VALUE(&previous, p);
		ZVAL_COPY(p, type);
		Z_PROP_FLAG_P(p) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	/* new self($type); UNDEF = pending exception */
	static zv::Val create(zval *type)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_value_of_type) != SUCCESS)) return zv::Val();
		ValueOfType(Z_OBJ(object)).construct(type);
		return zv::Val::adopt(object);
	}

	/* the slot (borrowed); NULL with an Error pending when the constructor
	 * never ran — the twin's typed-property read raises the same */
	[[nodiscard]] zval *type() const { return slot(self); }

	static zval *slot(zend_object *object)
	{
		zval *p = OBJ_PROP_NUM(object, slots::type);
		if (UNEXPECTED(Z_TYPE_P(p) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$type must not be accessed before initialization", ZSTR_VAL(pt_ce_value_of_type->name));
			return NULL;
		}
		return p;
	}

	/* $this->type->getReferencedClasses() */
	zv::Val getReferencedClasses() const { return callType(PT_LC("getreferencedclasses"), 0, NULL); }

	/* $this->type->getReferencedTemplateTypes($positionVariance) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const { return callType(PT_LC("getreferencedtemplatetypes"), 1, positionVariance); }

	/* $type instanceof self && $this->type->equals($type->type); false with
	 * an exception pending */
	bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_value_of_type)) {
			out = false;
			return true;
		}
		zval *theirs = slot(Z_OBJ_P(type));
		if (UNEXPECTED(theirs == NULL)) return false;
		zv::Val equal = callType(PT_LC("equals"), 1, theirs);
		if (UNEXPECTED(equal.isUndef())) return false;
		out = zend_is_true(equal.raw());
		return true;
	}

	/* sprintf('value-of<%s>', $this->type->describe($level)) */
	zv::Val describe(zval *level) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_describe_generic_of(PT_LC("value-of"), t, level);
	}

	/* !TypeUtils::containsTemplateType($this->type); false = pending exception */
	[[nodiscard]] bool isResolvable(bool &out) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return false;
		bool contains;
		if (UNEXPECTED(!pt_type_utils_contains_template_type(t, contains))) return false;
		out = !contains;
		return true;
	}

	/* for an enum type the union of its cases' backing value types (never
	 * without any, int|string for a template type bound to a BackedEnum
	 * without cases), the iterable value type otherwise; UNDEF = pending
	 * exception */
	zv::Val getResult() const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zend_long isEnum = pt_type_call_trinary(Z_OBJ_P(t), PT_LC("isenum"), 0, NULL);
		if (UNEXPECTED(isEnum < 0)) return zv::Val();
		if (isEnum == PT_TRI_YES) {
			zv::Val enumCases = callType(PT_LC("getenumcases"), 0, NULL);
			if (UNEXPECTED(enumCases.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(enumCases.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getEnumCases() must return an array");
				return zv::Val();
			}
			HashTable *cases = Z_ARRVAL_P(enumCases.raw());
			if (zend_hash_num_elements(cases) == 0) {
				bool isTemplate;
				if (UNEXPECTED(!pt_type_instanceof(t, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
				if (isTemplate) {
					zend_long backedEnumBound = boundIsBackedEnum(t);
					if (UNEXPECTED(backedEnumBound < 0)) return zv::Val();
					if (backedEnumBound == 1) return intOrString();
				}
			}
			zv::Arr valueTypes = zv::Arr::create(zend_hash_num_elements(cases));
			for (zv::ArrayEntry entry : zv::ArrRef(enumCases.raw())) {
				zv::Ref enumCase = entry.value().deref();
				if (UNEXPECTED(!enumCase.isObject())) {
					zend_type_error("phpstan_turbo: getEnumCases() must return a list of EnumCaseObjectType");
					return zv::Val();
				}
				zv::Val valueType = pt_type_call(enumCase.asObject(), PT_LC("getbackingvaluetype"), 0, NULL);
				if (UNEXPECTED(valueType.isUndef())) return zv::Val();
				if (valueType.isNull()) continue;
				valueTypes.push(std::move(valueType));
			}
			uint32_t count = zend_hash_num_elements(valueTypes.table());
			if (count == 0) return pt_type_new_never_type();
			if (count == 1) return zv::Val::copyOf(zv::ArrRef(valueTypes.raw()).findIndex(0));
			return pt_type_new_union(std::move(valueTypes));
		}
		return callType(PT_LC("getiterablevaluetype"), 0, NULL);
	}

	/* new self($cb($this->type)) when the callback changed it, $this
	 * otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return traversed(pt_type_traverse_call(fci, fcc, t));
	}

	/* $this for a $right of another class, else traverse() with $right's
	 * type as the callback's second argument */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_value_of_type)) return thisValue();
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval *theirs = slot(Z_OBJ_P(right));
		if (UNEXPECTED(theirs == NULL)) return zv::Val();
		return traversed(pt_type_traverse_call(fci, fcc, t, theirs));
	}

	/* new GenericTypeNode(new IdentifierTypeNode('value-of'), [$this->type->toPhpDocNode()]) */
	zv::Val toPhpDocNode() const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_generic_node_of(PT_LC("value-of"), t);
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->type->method(...$args); UNDEF = pending exception */
	zv::Val callType(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_call(Z_OBJ_P(t), lcname, len, argc, argv);
	}

	/* (new ObjectType('BackedEnum'))->isSuperTypeOf($templateType->getBound())->yes();
	 * -1 = pending exception */
	[[nodiscard]] static zend_long boundIsBackedEnum(zval *templateType)
	{
		zend_string *className = zend_string_init(PT_LC("BackedEnum"), 0);
		zval backedEnumRaw;
		bool created = pt_object_type_new(&backedEnumRaw, className);
		zend_string_release(className);
		if (UNEXPECTED(!created)) return -1;
		zv::Val backedEnum = zv::Val::adopt(backedEnumRaw);
		zv::Val bound = pt_type_call(Z_OBJ_P(templateType), PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(bound.isUndef())) return -1;
		if (UNEXPECTED(!zv::Ref(bound.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getBound() must return %s", ptcls::type);
			return -1;
		}
		zv::Val result = pt_type_op(Z_OBJ_P(backedEnum.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, bound.raw());
		if (UNEXPECTED(result.isUndef())) return -1;
		zend_long value = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(value < 0)) return -1;
		return value == PT_TRI_YES ? 1 : 0;
	}

	/* new UnionType([new IntegerType(), new StringType()]) */
	static zv::Val intOrString()
	{
		zval integerRaw, stringRaw;
		if (UNEXPECTED(!pt_integer_type_new(&integerRaw))) return zv::Val();
		zv::Val integer = zv::Val::adopt(integerRaw);
		if (UNEXPECTED(!pt_string_type_new(&stringRaw))) return zv::Val();
		zv::Val string = zv::Val::adopt(stringRaw);
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(integer));
		types.push(std::move(string));
		return pt_type_new_union(std::move(types));
	}

	/* the tail of traverse()/traverseSimultaneously(): `$this->type === $type
	 * ? $this : new self($type)` (UNDEF in = UNDEF out) */
	zv::Val traversed(zv::Val type) const
	{
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zval *t = this->type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		if (pt_type_same_object(t, type.raw())) return thisValue();
		return create(type.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::ValueOfType;

bool pt_value_of_type_new(zval *out, zval *type)
{
	return pt_val_into(ValueOfType::create(type), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ValueOfType(Z_OBJ_P(ZEND_THIS))

void pt_register_value_of_type()
{
	reg::Class cls("PHPStan\\Type\\ValueOfType");
	ptdecl::ValueOfType::declareClass(cls);
	/* the slot must stay first (slots::type); the trait registrar
	 * declares $result after it */
	ptdecl::ValueOfType::declareProperties(cls);

	cls.method<&ValueOfType::construct, zp::TypeObj>(sigs::__construct);

	cls.method<&ValueOfType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &ValueOfType::getReferencedClasses>();

	cls.method<&ValueOfType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&ValueOfType::equals, zp::TypeObj>(sigs::equals);

	cls.method<&ValueOfType::describe, zp::Obj>(sigs::describe);

	cls.method<&ValueOfType::isResolvable>(sigs::isResolvable);

	cls.method<&ValueOfType::getResult>(sigs::getResult);

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

	cls.method<&ValueOfType::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::ValueOfType::registerTraits(cls);

	cls.shadow(&pt_ce_value_of_type);
}

/* }}} */
