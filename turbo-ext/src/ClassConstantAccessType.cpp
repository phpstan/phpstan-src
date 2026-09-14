/*
 * PHPStanTurbo\ClassConstantAccessType — native implementation of
 * PHPStan\Type\ClassConstantAccessType.
 *
 * State is the twin's two promoted `private Type $type` / `private string
 * $constantName` in slots 0 and 1; LateResolvableTypeTrait's `private ?Type
 * $result` follows them, declared by the shared registrar in TypeTraits.cpp
 * that also supplies the trait's forwards; NonGeneralizableTypeTrait's
 * generalize() comes from its registrar.
 *
 * The one $this-call the twin makes (describe() through $this->resolve())
 * is the trait's body, run directly — the class is final. Another
 * instance's private slots are read directly, as the twin does from inside
 * the class.
 */

#include "TypeTraits.h"
#include "generated/ClassConstantAccessType.h"

namespace slots = ptdecl::ClassConstantAccessType::slot;
namespace sigs = ptdecl::ClassConstantAccessType::sig;

zend_class_entry *pt_ce_class_constant_access_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\ClassConstantAccessType. State lives in the PHP
 * object's slots. */
class ClassConstantAccessType
{
public:
	explicit ClassConstantAccessType(zend_object *self) : self(self) {}

	/* __construct(private Type $type, private string $constantName); both
	 * borrowed */
	void construct(zval *type, zend_string *constantName)
	{
		writeSlot(slots::type, type);
		zval name;
		ZVAL_STR(&name, constantName);
		writeSlot(slots::constantName, &name);
	}

	/* new self($type, $constantName); UNDEF = pending exception */
	static zv::Val create(zval *type, zend_string *constantName)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_class_constant_access_type) != SUCCESS)) return zv::Val();
		ClassConstantAccessType(Z_OBJ(object)).construct(type, constantName);
		return zv::Val::adopt(object);
	}

	/* the slots (borrowed); NULL with an Error pending when the constructor
	 * never ran */
	[[nodiscard]] zval *type() const { return slot(self, slots::type, "type"); }
	zval *constantName() const { return slot(self, slots::constantName, "constantName"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_class_constant_access_type, name); }

	/* $this->type->getReferencedClasses() */
	zv::Val getReferencedClasses() const { return callType(PT_LC("getreferencedclasses"), 0, NULL); }

	/* $this->type->getReferencedTemplateTypes($positionVariance) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const { return callType(PT_LC("getreferencedtemplatetypes"), 1, positionVariance); }

	/* $type instanceof self && $this->constantName === $type->constantName
	 * && $this->type->equals($type->type); false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_class_constant_access_type)) {
			out = false;
			return true;
		}
		zval *name = constantName();
		if (UNEXPECTED(name == NULL)) return false;
		zval *theirName = slot(Z_OBJ_P(type), slots::constantName, "constantName");
		if (UNEXPECTED(theirName == NULL)) return false;
		if (!zend_string_equals(Z_STR_P(name), Z_STR_P(theirName))) {
			out = false;
			return true;
		}
		zval *theirType = slot(Z_OBJ_P(type), slots::type, "type");
		if (UNEXPECTED(theirType == NULL)) return false;
		zv::Val equal = callType(PT_LC("equals"), 1, theirType);
		if (UNEXPECTED(equal.isUndef())) return false;
		out = zend_is_true(equal.raw());
		return true;
	}

	/* $this->resolve()->describe($level) */
	zv::Val describe(zval *level) const
	{
		zv::Val resolved = pt_type_late_resolvable_resolve(self, pt_ce_class_constant_access_type);
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(resolved.raw()).isObject())) {
			zend_type_error("phpstan_turbo: resolve() must return %s", ptcls::type);
			return zv::Val();
		}
		return pt_type_op(Z_OBJ_P(resolved.raw()), PT_OP_DESCRIBE, 1, level);
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

	/* $this->type->getConstant($this->constantName)->getValueType() when
	 * $this->type->hasConstant($this->constantName)->yes(), new ErrorType()
	 * otherwise; UNDEF = pending exception */
	zv::Val getResult() const
	{
		zval *t = type();
		zval *name = t != NULL ? constantName() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zend_long has = pt_type_call_trinary(Z_OBJ_P(t), PT_LC("hasconstant"), 1, name);
		if (UNEXPECTED(has < 0)) return zv::Val();
		if (has == PT_TRI_YES) {
			zv::Val constant = pt_type_call(Z_OBJ_P(t), PT_LC("getconstant"), 1, name);
			if (UNEXPECTED(constant.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(constant.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getConstant() must return an object");
				return zv::Val();
			}
			return pt_type_call(Z_OBJ_P(constant.raw()), PT_LC("getvaluetype"), 0, NULL);
		}
		return pt_type_new_error_type();
	}

	/* new self($cb($this->type), $this->constantName) when the callback
	 * changed the type, $this otherwise; UNDEF = pending exception */
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
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_class_constant_access_type)) return thisValue();
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval *theirType = slot(Z_OBJ_P(right), slots::type, "type");
		if (UNEXPECTED(theirType == NULL)) return zv::Val();
		return traversed(pt_type_traverse_call(fci, fcc, t, theirType));
	}

	/* new ConstTypeNode(new ConstFetchNode('static', $this->constantName)) */
	zv::Val toPhpDocNode() const
	{
		zval *name = constantName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval args[2];
		ZVAL_STRINGL(&args[0], "static", sizeof("static") - 1);
		ZVAL_COPY_VALUE(&args[1], name);
		zv::Val constFetch = pt_type_new(PT_CLASS_CONST_FETCH_NODE, 2, args);
		zval_ptr_dtor(&args[0]);
		if (UNEXPECTED(constFetch.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_CONST_TYPE_NODE, 1, constFetch.raw());
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* $this->type->method(...$args); UNDEF = pending exception */
	zv::Val callType(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_call(Z_OBJ_P(t), lcname, len, argc, argv);
	}

	/* the tail of traverse()/traverseSimultaneously(): `$this->type === $type
	 * ? $this : new self($type, $this->constantName)` (UNDEF in = UNDEF out) */
	zv::Val traversed(zv::Val type) const
	{
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zval *t = this->type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		if (pt_type_same_object(t, type.raw())) return thisValue();
		zval *name = constantName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return create(type.raw(), Z_STR_P(name));
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClassConstantAccessType;

bool pt_class_constant_access_type_new(zval *out, zval *type, zend_string *constantName)
{
	return pt_val_into(ClassConstantAccessType::create(type, constantName), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ClassConstantAccessType(Z_OBJ_P(ZEND_THIS))

void pt_register_class_constant_access_type()
{
	reg::Class cls("PHPStan\\Type\\ClassConstantAccessType");
	ptdecl::ClassConstantAccessType::declareClass(cls);
	/* the slots must stay in this order (PT_CCA_PROP_*); the trait registrar
	 * declares $result after them */
	ptdecl::ClassConstantAccessType::declareProperties(cls);

	cls.method<&ClassConstantAccessType::construct, zp::Obj, zp::Str>(sigs::__construct);

	cls.method<&ClassConstantAccessType::getReferencedClasses>(sigs::getReferencedClasses);

	cls.method<&ClassConstantAccessType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&ClassConstantAccessType::equals, zp::Obj>(sigs::equals);

	cls.method<&ClassConstantAccessType::describe, zp::Obj>(sigs::describe);

	cls.method<&ClassConstantAccessType::isResolvable>(sigs::isResolvable);

	cls.method<&ClassConstantAccessType::getResult>(sigs::getResult);

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

	cls.method<&ClassConstantAccessType::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::ClassConstantAccessType::registerTraits(cls);

	cls.shadow(&pt_ce_class_constant_access_type);
}

/* }}} */
