/*
 * PHPStanTurbo\StringSet — native implementation of PHPStan\Type\StringSet.
 *
 * Declared as PHPStan\Type\StringSet itself at activation: final, no parent,
 * no interfaces. State is the twin's `private array $constants`, `private
 * ?Type $type`, `private bool $digits` and `private bool $whitespaceFree`,
 * plus its `private ?array $values` lookup of the constants (unused here),
 * declared typed property slots, so the std object handlers do GC/clone. The
 * twin's private constructor is not declared: the sets are made by the
 * factories only, natively.
 *
 * The twin's private methods live only here, in StringSetOps, method for
 * method. ValueSet.cpp reads the slots through the pt_string_set_*() entries.
 */

#include "TypeTraits.h"
#include "IetrSupport.h"
#include "generated/StringSet.h"

namespace slots = ptdecl::StringSet::slot;
namespace sigs = ptdecl::StringSet::sig;

static zend_class_entry *pt_ce_string_set = nullptr;

namespace phpstanturbo {

using namespace phpstanturbo::ietr;

/* a borrowed view of a StringSet's slots */
class StringSetView
{
public:
	explicit StringSetView(zend_object *o) : o(o) {}

	HashTable *constants() const
	{
		zval *value = OBJ_PROP_NUM(o, slots::constants);
		return Z_TYPE_P(value) == IS_ARRAY ? Z_ARRVAL_P(value) : (HashTable *) &zend_empty_array;
	}
	zval *type() const
	{
		zval *value = OBJ_PROP_NUM(o, slots::type);
		return Z_TYPE_P(value) == IS_OBJECT ? value : nullptr;
	}
	bool digits() const { return Z_TYPE_P(OBJ_PROP_NUM(o, slots::digits)) == IS_TRUE; }
	bool whitespaceFree() const { return Z_TYPE_P(OBJ_PROP_NUM(o, slots::whitespaceFree)) == IS_TRUE; }
	bool isEmpty() const { return zend_hash_num_elements(constants()) == 0 && type() == nullptr; }

private:
	zend_object *o;
};

/* Mirrors the private methods of the twin; UNDEF / -1 = pending exception. */
class StringSetOps
{
public:
	static constexpr const char *WHITESPACE = " \t\n\r\v\f";
	static constexpr const char *NUMBER = "([+-]?(?:\\d+\\.\\d*|\\.\\d+|(\\d+))([eE][+-]?\\d+)?)";

	/* new self($constants, $type, $digits, $whitespaceFree); type UNDEF or null for none */
	static zv::Val newStringSet(zv::Arr &constants, zv::Val type, bool digits, bool whitespaceFree)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_string_set) != SUCCESS)) return zv::Val();
		zend_object *o = Z_OBJ(object);
		zval *constantsSlot = OBJ_PROP_NUM(o, slots::constants);
		zval *typeSlot = OBJ_PROP_NUM(o, slots::type);
		zval *digitsSlot = OBJ_PROP_NUM(o, slots::digits);
		zval *whitespaceFreeSlot = OBJ_PROP_NUM(o, slots::whitespaceFree);
		zval_ptr_dtor(constantsSlot);
		zval_ptr_dtor(typeSlot);
		ZVAL_COPY(constantsSlot, constants.raw());
		if (type.isUndef() || Z_TYPE_P(type.raw()) == IS_NULL) {
			ZVAL_NULL(typeSlot);
		} else {
			zval taken = type.take();
			ZVAL_COPY_VALUE(typeSlot, &taken);
		}
		ZVAL_BOOL(digitsSlot, digits);
		ZVAL_BOOL(whitespaceFreeSlot, whitespaceFree);
		Z_PROP_FLAG_P(constantsSlot) = 0; /* no longer IS_PROP_UNINIT */
		Z_PROP_FLAG_P(typeSlot) = 0;
		Z_PROP_FLAG_P(digitsSlot) = 0;
		Z_PROP_FLAG_P(whitespaceFreeSlot) = 0;
		return zv::Val::adopt(object);
	}

	static zv::Val createEmpty()
	{
		zv::Arr constants = zv::Arr::create(0);
		return newStringSet(constants, zv::Val::null(), false, false);
	}

	static zv::Val createConstant(zval *string)
	{
		zv::Arr constants = zv::Arr::create(1);
		constants.push(zv::Ref(string));
		return newStringSet(constants, zv::Val::null(), false, false);
	}

	static zv::Val createType(zval *strings)
	{
		IETR_TRI(decimal, triOn(strings, mIsDecimalIntegerString));
		zv::Arr constants = zv::Arr::create(0);
		return newStringSet(constants, zv::Val::copyOf(zv::Ref(strings)), false, decimal == PT_TRI_YES);
	}

	static zv::Val toNumberStrings(StringSetView s, bool digits)
	{
		zv::Arr constants = zv::Arr::copyOfTable(s.constants());
		return newStringSet(constants, copyOrNull(s.type()), digits, true);
	}

	static zv::Val union_(StringSetView a, StringSetView b)
	{
		zv::Arr constants = zv::Arr::copyOfTable(a.constants());
		zval *entry;
		ZEND_HASH_FOREACH_VAL(b.constants(), entry) {
			IETR_VAL(value, constantStringValue(entry));
			int seen = contains(constants.table(), Z_STR_P(value.raw()));
			if (UNEXPECTED(seen < 0)) return zv::Val();
			if (seen) continue;
			constants.push(zv::Ref(entry));
		} ZEND_HASH_FOREACH_END();

		if (a.type() == nullptr || b.type() == nullptr) {
			StringSetView typed = a.type() == nullptr ? b : a;
			return newStringSet(constants, copyOrNull(typed.type()), typed.digits(), typed.whitespaceFree());
		}

		IETR_VAL(type, union2(a.type(), b.type()));
		return newStringSet(constants, std::move(type), a.digits() && b.digits(), a.whitespaceFree() && b.whitespaceFree());
	}

	static zv::Val intersect(StringSetView a, StringSetView b)
	{
		zv::Arr constants = zv::Arr::create(0);
		StringSetView pairs[2][2] = {{a, b}, {b, a}};
		for (auto &pair : pairs) {
			StringSetView set = pair[0];
			StringSetView otherSet = pair[1];
			zval *entry;
			ZEND_HASH_FOREACH_VAL(set.constants(), entry) {
				IETR_VAL(value, constantStringValue(entry));
				int seen = contains(constants.table(), Z_STR_P(value.raw()));
				if (UNEXPECTED(seen < 0)) return zv::Val();
				if (seen) continue;
				int inOther = contains(otherSet.constants(), Z_STR_P(value.raw()));
				if (UNEXPECTED(inOther < 0)) return zv::Val();
				if (!inOther) {
					if (otherSet.type() == nullptr) continue;
					IETR_TRI(isSuperType, triOn(otherSet.type(), mIsSuperTypeOf, 1, entry));
					if (isSuperType == PT_TRI_NO) continue;
				}
				constants.push(zv::Ref(entry));
			} ZEND_HASH_FOREACH_END();
		}

		if (a.type() == nullptr || b.type() == nullptr) {
			return newStringSet(constants, zv::Val::null(), false, false);
		}

		zv::Args argv{a.type(), b.type()};
		IETR_VAL(type, pt_type_combinator_intersect(2, argv));
		return newStringSet(constants, std::move(type), a.digits() || b.digits(), a.whitespaceFree() || b.whitespaceFree());
	}

	static zv::Val toType(StringSetView s)
	{
		zv::Arr types = zv::Arr::copyOfTable(s.constants());
		if (s.type() != nullptr) types.push(zv::Ref(s.type()));
		return unionOf(types);
	}

	/* Mirrors concatenate(). */
	static zv::Val concatenate(StringSetView left, StringSetView right, zval *phpVersion)
	{
		if (left.isEmpty() || right.isEmpty()) return newNeverType();
		int leftEmptyString = isEmptyString(left);
		if (UNEXPECTED(leftEmptyString < 0)) return zv::Val();
		if (leftEmptyString) return toType(right);
		int rightEmptyString = isEmptyString(right);
		if (UNEXPECTED(rightEmptyString < 0)) return zv::Val();
		if (rightEmptyString) return toType(left);

		// we limit the number of union-types for performance reasons
		zend_long leftCount = zend_hash_num_elements(left.constants());
		zend_long rightCount = zend_hash_num_elements(right.constants());
		if (left.type() == nullptr && right.type() == nullptr && leftCount * rightCount <= PT_IETR_CALCULATE_SCALARS_LIMIT) {
			zv::Arr strings = zv::Arr::create((uint32_t) (leftCount * rightCount));
			zval *leftConstant;
			ZEND_HASH_FOREACH_VAL(left.constants(), leftConstant) {
				int leftConstantEmpty = constantStringValueIsEmpty(leftConstant);
				if (UNEXPECTED(leftConstantEmpty < 0)) return zv::Val();
				zval *rightConstant;
				if (leftConstantEmpty) {
					ZEND_HASH_FOREACH_VAL(right.constants(), rightConstant) {
						strings.push(zv::Ref(rightConstant));
					} ZEND_HASH_FOREACH_END();
					continue;
				}

				ZEND_HASH_FOREACH_VAL(right.constants(), rightConstant) {
					int rightConstantEmpty = constantStringValueIsEmpty(rightConstant);
					if (UNEXPECTED(rightConstantEmpty < 0)) return zv::Val();
					if (rightConstantEmpty) {
						strings.push(zv::Ref(leftConstant));
						continue;
					}
					IETR_VAL(appended, callOn(leftConstant, mAppend, 1, rightConstant));
					strings.push(std::move(appended));
				} ZEND_HASH_FOREACH_END();
			} ZEND_HASH_FOREACH_END();

			return unionOf(strings);
		}

		zv::Arr accessoryTypes = zv::Arr::create(6);
		{
			IETR_TRI(leftNonEmpty, isNonEmpty(left));
			IETR_TRI(rightNonEmpty, isNonEmpty(right));
			int accessory = 0; /* 1 = non-falsy, 2 = non-empty */
			if (leftNonEmpty && rightNonEmpty) {
				accessory = 1;
			} else {
				IETR_TRI(leftNonFalsy, isNonFalsy(left));
				IETR_TRI(rightNonFalsy, isNonFalsy(right));
				if (leftNonFalsy || rightNonFalsy) {
					accessory = 1;
				} else if (leftNonEmpty || rightNonEmpty) {
					accessory = 2;
				}
			}
			if (accessory != 0) {
				zval type;
				bool created = accessory == 1 ? pt_accessory_non_falsy_string_type_new(&type) : pt_accessory_non_empty_string_type_new(&type);
				if (UNEXPECTED(!created)) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(type));
			}
		}

		{
			IETR_TRI(leftLiteral, isLiteral(left));
			IETR_TRI(rightLiteral, isLiteral(right));
			if (leftLiteral && rightLiteral) {
				zval type;
				if (UNEXPECTED(!pt_accessory_literal_string_type_new(&type))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(type));
			}
		}

		{
			IETR_TRI(leftLowercase, isLowercase(left));
			IETR_TRI(rightLowercase, isLowercase(right));
			if (leftLowercase && rightLowercase) {
				zval type;
				if (UNEXPECTED(!pt_accessory_lowercase_string_type_new(&type))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(type));
			}
		}

		{
			IETR_TRI(leftUppercase, isUppercase(left));
			IETR_TRI(rightUppercase, isUppercase(right));
			if (leftUppercase && rightUppercase) {
				zval type;
				if (UNEXPECTED(!pt_accessory_uppercase_string_type_new(&type))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(type));
			}
		}

		{
			IETR_TRI(numeric, isNumericOrEmpty(left, phpVersion));
			if (numeric) {
				// '' . '' is not numeric
				IETR_TRI(leftNonEmpty, isNonEmpty(left));
				IETR_TRI(rightNonEmpty, isNonEmpty(right));
				numeric = leftNonEmpty || rightNonEmpty;
			}
			if (numeric) {
				// '1 ' is numeric on PHP 8, '1 5' is not
				IETR_TRI(endsInWhitespace, canEndInWhitespace(left, phpVersion));
				numeric = !endsInWhitespace;
			}
			if (numeric) {
				IETR_TRI(afterInteger, isDecimalInteger(left));
				IETR_TRI(suffix, isNumberSuffix(right, afterInteger, phpVersion));
				numeric = suffix;
			}
			if (numeric) {
				zval type;
				if (UNEXPECTED(!pt_accessory_numeric_string_type_new(&type))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(type));
			}
		}

		if (zend_hash_num_elements(accessoryTypes.table()) > 0) {
			IETR_VAL(stringType, newStringType());
			accessoryTypes.push(std::move(stringType));
			return newIntersectionType(accessoryTypes);
		}

		return newStringType();
	}

	/* {{{ private methods: 1 / 0, -1 = pending exception */

	static int isEmptyString(StringSetView s)
	{
		if (s.type() != nullptr || zend_hash_num_elements(s.constants()) != 1) return 0;
		zval *constant = zend_hash_index_find(s.constants(), 0);
		if (UNEXPECTED(constant == NULL)) return 0;
		return constantStringValueIsEmpty(constant);
	}

	/* the constants all pass `test` and the type answers yes to `query` */
	template <typename Test>
	static int every(StringSetView s, const Method &query, Test test)
	{
		zval *constant;
		ZEND_HASH_FOREACH_VAL(s.constants(), constant) {
			zv::Val value = constantStringValue(constant);
			if (UNEXPECTED(value.isUndef())) return -1;
			if (!test(Z_STR_P(value.raw()))) return 0;
		} ZEND_HASH_FOREACH_END();
		if (s.type() == nullptr) return 1;
		zend_long answer = triOn(s.type(), query);
		if (UNEXPECTED(answer < 0)) return -1;
		return answer == PT_TRI_YES ? 1 : 0;
	}

	static int isNonEmpty(StringSetView s)
	{
		return every(s, mIsNonEmptyString, [](zend_string *value) { return ZSTR_LEN(value) != 0; });
	}

	static int isNonFalsy(StringSetView s)
	{
		return every(s, mIsNonFalsyString, [](zend_string *value) {
			return !(ZSTR_LEN(value) == 0 || (ZSTR_LEN(value) == 1 && ZSTR_VAL(value)[0] == '0'));
		});
	}

	static int isLiteral(StringSetView s)
	{
		if (s.type() == nullptr) return 1;
		zend_long answer = triOn(s.type(), mIsLiteralString);
		if (UNEXPECTED(answer < 0)) return -1;
		return answer == PT_TRI_YES ? 1 : 0;
	}

	static int isLowercase(StringSetView s)
	{
		return every(s, mIsLowercaseString, [](zend_string *value) {
			zend_string *lower = zend_string_tolower(value);
			bool equal = zend_string_equals(lower, value);
			zend_string_release(lower);
			return equal;
		});
	}

	static int isUppercase(StringSetView s)
	{
		return every(s, mIsUppercaseString, [](zend_string *value) {
			zend_string *upper = zend_string_toupper(value);
			bool equal = zend_string_equals(upper, value);
			zend_string_release(upper);
			return equal;
		});
	}

	static int isDecimalInteger(StringSetView s)
	{
		return every(s, mIsDecimalIntegerString, [](zend_string *value) {
			zval valueZv;
			ZVAL_STR(&valueZv, value);
			zend_string *roundTrip = zend_long_to_str(zval_get_long(&valueZv));
			bool equal = zend_string_equals(roundTrip, value);
			zend_string_release(roundTrip);
			return equal;
		});
	}

	static int isNumericOrEmpty(StringSetView s, zval *phpVersion)
	{
		zval *constant;
		ZEND_HASH_FOREACH_VAL(s.constants(), constant) {
			zv::Val value = constantStringValue(constant);
			if (UNEXPECTED(value.isUndef())) return -1;
			zend_string *string = Z_STR_P(value.raw());
			if (ZSTR_LEN(string) == 0) continue;
			int numeric = isNumeric(string, phpVersion);
			if (numeric <= 0) return numeric;
		} ZEND_HASH_FOREACH_END();
		if (s.type() == nullptr) return 1;
		zend_long answer = triOn(s.type(), mIsNumericString);
		if (UNEXPECTED(answer < 0)) return -1;
		return answer == PT_TRI_YES ? 1 : 0;
	}

	static int canEndInWhitespace(StringSetView s, zval *phpVersion)
	{
		zval *constant;
		ZEND_HASH_FOREACH_VAL(s.constants(), constant) {
			zv::Val value = constantStringValue(constant);
			if (UNEXPECTED(value.isUndef())) return -1;
			zend_string *string = Z_STR_P(value.raw());
			if (trimmedLength(string) != ZSTR_LEN(string)) return 1;
		} ZEND_HASH_FOREACH_END();
		if (s.type() == nullptr || s.whitespaceFree()) return 0;
		bool allows;
		if (UNEXPECTED(!pt_php_version_answer(phpVersion, PT_PHP_VERSION_ALLOWS_TRAILING_WHITESPACE_IN_NUMERIC_STRINGS, allows))) return -1;
		return allows ? 1 : 0;
	}

	static int isNumberSuffix(StringSetView s, bool afterInteger, zval *phpVersion)
	{
		zval *constant;
		ZEND_HASH_FOREACH_VAL(s.constants(), constant) {
			zv::Val value = constantStringValue(constant);
			if (UNEXPECTED(value.isUndef())) return -1;
			zend_string *string = Z_STR_P(value.raw());
			if (ZSTR_LEN(string) == 0) continue;
			int numeric = isNumeric(string, phpVersion);
			if (UNEXPECTED(numeric < 0)) return -1;
			if (!numeric || !(afterInteger ? isNumberAfterInteger(string) : isDecimalDigits(string))) return 0;
		} ZEND_HASH_FOREACH_END();
		return s.type() == nullptr || s.digits() ? 1 : 0;
	}

	/* rtrim($value, self::WHITESPACE)'s length */
	static size_t trimmedLength(zend_string *value)
	{
		size_t length = ZSTR_LEN(value);
		while (length > 0 && strchr(WHITESPACE, ZSTR_VAL(value)[length - 1]) != NULL) length--;
		return length;
	}

	static bool isWhitespace(char c)
	{
		return c != '\0' && strchr(WHITESPACE, c) != NULL;
	}

	static bool isDigit(char c)
	{
		return c >= '0' && c <= '9';
	}

	/* the match of '/^[WHITESPACE]*' . NUMBER (. '[WHITESPACE]*$/D' when
	 * anchored, without the whitespace for PHP 7): the span of the number
	 * and whether it is an integer, without a fraction or an exponent */
	static bool matchNumber(zend_string *value, bool anchored, bool trailingWhitespace, size_t &start, size_t &length, bool &integerSyntax)
	{
		const char *s = ZSTR_VAL(value);
		size_t n = ZSTR_LEN(value);
		size_t i = 0;
		while (i < n && isWhitespace(s[i])) i++;
		start = i;
		if (i < n && (s[i] == '+' || s[i] == '-')) i++;
		size_t integerDigits = 0;
		while (i < n && isDigit(s[i])) {
			i++;
			integerDigits++;
		}
		bool fraction = false;
		if (integerDigits > 0) {
			if (i < n && s[i] == '.') {
				i++;
				while (i < n && isDigit(s[i])) i++;
				fraction = true;
			}
		} else {
			if (!(i + 1 < n && s[i] == '.' && isDigit(s[i + 1]))) return false;
			i++;
			while (i < n && isDigit(s[i])) i++;
			fraction = true;
		}
		bool exponent = false;
		if (i < n && (s[i] == 'e' || s[i] == 'E')) {
			size_t j = i + 1;
			if (j < n && (s[j] == '+' || s[j] == '-')) j++;
			size_t exponentDigits = 0;
			while (j < n && isDigit(s[j])) {
				j++;
				exponentDigits++;
			}
			if (exponentDigits > 0) {
				i = j;
				exponent = true;
			}
		}
		length = i - start;
		integerSyntax = integerDigits > 0 && !fraction && !exponent;
		if (anchored) {
			if (trailingWhitespace) {
				while (i < n && isWhitespace(s[i])) i++;
			}
			if (i != n) return false;
		}
		return true;
	}

	/* toNumber(): +$numericString into `number`, the overflow direction */
	static int toNumber(zend_string *value, size_t start, size_t length, bool integerSyntax, zval *number)
	{
		zend_long lval = 0;
		double dval = 0.0;
		zend_uchar type = is_numeric_string(ZSTR_VAL(value) + start, length, &lval, &dval, false);
		if (UNEXPECTED(type == 0)) {
			pt_throw_should_not_happen();
			return -2;
		}
		if (type == IS_LONG) {
			ZVAL_LONG(number, lval);
			return 0;
		}
		ZVAL_DOUBLE(number, dval);
		if (integerSyntax) return dval < 0 ? -1 : 1;
		return 0;
	}

	/* parseNumericString(): 1 with the number and the overflow direction, 0
	 * for null, -1 = pending exception */
	static int parseNumericString(zend_string *value, zval *phpVersion, zval *number, int &overflow)
	{
		bool trailingWhitespace;
		if (UNEXPECTED(!pt_php_version_answer(phpVersion, PT_PHP_VERSION_ALLOWS_TRAILING_WHITESPACE_IN_NUMERIC_STRINGS, trailingWhitespace))) return -1;
		size_t start, length;
		bool integerSyntax;
		if (!matchNumber(value, true, trailingWhitespace, start, length, integerSyntax)) return 0;
		int direction = toNumber(value, start, length, integerSyntax, number);
		if (UNEXPECTED(direction == -2)) return -1;
		overflow = direction;
		return 1;
	}

	/* parseNumericPrefix(): 1 with the number, 0 for null, -1 = pending exception */
	static int parseNumericPrefix(zend_string *value, zval *number)
	{
		size_t start, length;
		bool integerSyntax;
		if (!matchNumber(value, false, false, start, length, integerSyntax)) return 0;
		return toNumber(value, start, length, integerSyntax, number) == -2 ? -1 : 1;
	}

	static int isNumeric(zend_string *value, zval *phpVersion)
	{
		zval number;
		int overflow;
		return parseNumericString(value, phpVersion, &number, overflow);
	}

	static int contains(HashTable *constants, zend_string *value)
	{
		zval *constant;
		ZEND_HASH_FOREACH_VAL(constants, constant) {
			zv::Val constantValue = constantStringValue(constant);
			if (UNEXPECTED(constantValue.isUndef())) return -1;
			if (zend_string_equals(Z_STR_P(constantValue.raw()), value)) return 1;
		} ZEND_HASH_FOREACH_END();
		return 0;
	}

	/* }}} */

private:
	static zv::Val copyOrNull(zval *value)
	{
		return value == nullptr ? zv::Val::null() : zv::Val::copyOf(zv::Ref(value));
	}
};

} // namespace phpstanturbo

using phpstanturbo::StringSetOps;
using phpstanturbo::StringSetView;

/* {{{ native entries */

zend_class_entry *pt_string_set_class()
{
	return pt_ce_string_set;
}

zv::Val pt_string_set_create_empty()
{
	return StringSetOps::createEmpty();
}

zv::Val pt_string_set_create_constant(zval *string)
{
	return StringSetOps::createConstant(string);
}

zv::Val pt_string_set_create_type(zval *strings)
{
	return StringSetOps::createType(strings);
}

zv::Val pt_string_set_to_number_strings(zval *set, bool digits)
{
	return StringSetOps::toNumberStrings(StringSetView(Z_OBJ_P(set)), digits);
}

zv::Val pt_string_set_union(zval *a, zval *b)
{
	return StringSetOps::union_(StringSetView(Z_OBJ_P(a)), StringSetView(Z_OBJ_P(b)));
}

zv::Val pt_string_set_intersect(zval *a, zval *b)
{
	return StringSetOps::intersect(StringSetView(Z_OBJ_P(a)), StringSetView(Z_OBJ_P(b)));
}

zv::Val pt_string_set_concatenate(zval *left, zval *right, zval *phpVersion)
{
	return StringSetOps::concatenate(StringSetView(Z_OBJ_P(left)), StringSetView(Z_OBJ_P(right)), phpVersion);
}

HashTable *pt_string_set_constants(zval *set)
{
	return StringSetView(Z_OBJ_P(set)).constants();
}

zval *pt_string_set_type(zval *set)
{
	return StringSetView(Z_OBJ_P(set)).type();
}

bool pt_string_set_is_empty(zval *set)
{
	return StringSetView(Z_OBJ_P(set)).isEmpty();
}

int pt_string_set_parse_numeric_string(zend_string *value, zval *phpVersion, zval *number, int &overflow)
{
	return StringSetOps::parseNumericString(value, phpVersion, number, overflow);
}

int pt_string_set_parse_numeric_prefix(zend_string *value, zval *number)
{
	return StringSetOps::parseNumericPrefix(value, number);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_SELF StringSetView(Z_OBJ_P(ZEND_THIS))

/* an argument of the twin's `self` / class-typed parameter: the TypeError
 * of its strict-types call for any other value */
static bool requireInstance(zval *value, zend_class_entry *ce, uint32_t position, const char *name)
{
	if (EXPECTED(ce != NULL && Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce))) return true;
	zend_type_error("%s::%s(): Argument #%u ($%s) must be of type %s, %s given", ZSTR_VAL(pt_ce_string_set->name), get_active_function_name(), position, name, ce != NULL ? ZSTR_VAL(ce->name) : "?", zend_zval_value_name(value));
	return false;
}

template <zv::Val (*Combine)(StringSetView, StringSetView)>
static void ZEND_FASTCALL pt_ss_combine(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *other;
	if (!zp::parse<zp::Obj>(execute_data, other)) RETURN_THROWS();
	if (UNEXPECTED(!requireInstance(other, pt_ce_string_set, 1, "other"))) RETURN_THROWS();
	PT_RETURN_VAL(Combine(PT_SELF, StringSetView(Z_OBJ_P(other))));
}

PT_MINIT_REGISTRATION(pt_register_string_set)
{
	reg::Class cls("PHPStan\\Type\\StringSet");
	ptdecl::StringSet::declareClass(cls);
	cls.privateClassConstantString("WHITESPACE", StringSetOps::WHITESPACE);
	cls.privateClassConstantString("NUMBER", StringSetOps::NUMBER);
	/* "constants", "type", "digits" and "whitespaceFree" must stay the
	 * declared properties in this order (slots::*) */
	ptdecl::StringSet::declareProperties(cls);

	cls.method(sigs::createEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StringSetOps::createEmpty());
	});
	cls.method(sigs::createConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *string;
		if (!zp::parse<zp::Obj>(execute_data, string)) RETURN_THROWS();
		if (UNEXPECTED(!requireInstance(string, pt_ce_constant_string_type, 1, "string"))) RETURN_THROWS();
		PT_RETURN_VAL(StringSetOps::createConstant(string));
	});
	cls.method(sigs::createType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *strings;
		if (!zp::parse<zp::TypeObj>(execute_data, strings)) RETURN_THROWS();
		PT_RETURN_VAL(StringSetOps::createType(strings));
	});
	cls.method(sigs::toNumberStrings, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool digits;
		if (!zp::parse<zp::Bool>(execute_data, digits)) RETURN_THROWS();
		PT_RETURN_VAL(StringSetOps::toNumberStrings(PT_SELF, digits));
	});
	cls.method(sigs::getConstantStrings, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_ARR(zend_array_dup(PT_SELF.constants()));
	});
	cls.method(sigs::getType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *type = PT_SELF.type();
		if (type == nullptr) RETURN_NULL();
		RETURN_COPY(type);
	});
	cls.method(sigs::isEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(PT_SELF.isEmpty());
	});
	cls.method(sigs::union_, pt_ss_combine<StringSetOps::union_>);
	cls.method(sigs::intersect, pt_ss_combine<StringSetOps::intersect>);
	cls.method(sigs::toType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StringSetOps::toType(PT_SELF));
	});
	cls.method(sigs::parseNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *value;
		zval *phpVersion;
		if (!zp::parse<zp::Str, zp::Obj>(execute_data, value, phpVersion)) RETURN_THROWS();
		zval number;
		int overflow;
		int parsed = StringSetOps::parseNumericString(value, phpVersion, &number, overflow);
		if (UNEXPECTED(parsed < 0)) RETURN_THROWS();
		if (parsed == 0) RETURN_NULL();
		array_init_size(return_value, 2);
		add_next_index_zval(return_value, &number);
		add_next_index_long(return_value, overflow);
	});
	cls.method(sigs::parseNumericPrefix, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *value;
		if (!zp::parse<zp::Str>(execute_data, value)) RETURN_THROWS();
		int parsed = StringSetOps::parseNumericPrefix(value, return_value);
		if (UNEXPECTED(parsed < 0)) RETURN_THROWS();
		if (parsed == 0) RETURN_NULL();
	});
	cls.method(sigs::concatenate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, right, phpVersion)) RETURN_THROWS();
		if (UNEXPECTED(!requireInstance(right, pt_ce_string_set, 1, "right"))) RETURN_THROWS();
		if (UNEXPECTED(!requireInstance(phpVersion, pt_class(PT_CLASS_PHP_VERSION), 2, "phpVersion"))) RETURN_THROWS();
		PT_RETURN_VAL(StringSetOps::concatenate(PT_SELF, StringSetView(Z_OBJ_P(right)), phpVersion));
	});

	cls.shadow(&pt_ce_string_set);
}

/* }}} */
