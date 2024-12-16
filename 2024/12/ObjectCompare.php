<?php

class ObjectCompare implements ScalarComparable, PropertyComparable
{
    public static function cmp(ScalarCondition | PropertyCondition | array $c, mixed $a, mixed $b): bool
    {
        if(!is_array($c)) {
            if (get_class($c) === 'ScalarCondition') {
                return static::scCompare($c, $a, $b);
            } else if (get_class($c) === 'PropertyCondition') {
                return static::propCompare($c, $a, $b);
            } else {
                return false;
            }
        }

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cl = get_class($c[$i]);
            if ($cl === 'ScalarCondition') {
                $cond = static::scCmp($c[$i], $a, $b) && $cond;
            } else if (get_class($c) === 'PropertyCondition') {
                $cond = static::propCmp($c[$i], $a, $b) && $cond;
            } else {
                $cond = false;
            }

            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function propCompare(PropertyCondition | array $c, mixed $a, mixed $b): bool
    {
        if(!is_array($c)) {
            switch ($c->o) {
                case Operator::EQUAL_TO: return static::propEquals($c, $a, $b);
                case Operator::GREATER_OR_EQUAL_TO: return static::propGte($c, $a, $b);
                case Operator::GREATER_THAN: return static::propGt($c, $a, $b);
                case Operator::LESSER_OR_EQUAL_TO: return static::propLte($c, $a, $b);
                case Operator::LESSER_THAN: return static::propLt($c, $a, $b);
                default: return false;
            }
        }

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::propCompare($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function propEquals(PropertyCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $a->{$c->property} === $b->{$c->property};

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::propEquals($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function propGt(PropertyCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $a->{$c->property} > $b->{$c->property};

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::propGt($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function propGte(PropertyCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $a->{$c->property} >= $b->{$c->property};

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::propGte($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function propLt(PropertyCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $a->{$c->property} < $b->{$c->property};

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::propLt($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function propLte(PropertyCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $a->{$c->property} <= $b->{$c->property};

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::propLt($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function scCompare(ScalarCondition | array $c, mixed $a, mixed $b): bool
    {
        if(!is_array($c)) {
            switch ($c->o) {
                case Operator::EQUAL_TO: return static::scEquals($c, $a, $b);
                case Operator::GREATER_OR_EQUAL_TO: return static::scGte($c, $a, $b);
                case Operator::GREATER_THAN: return static::scGt($c, $a, $b);
                case Operator::LESSER_OR_EQUAL_TO: return static::scLte($c, $a, $b);
                case Operator::LESSER_THAN: return static::scLt($c, $a, $b);
                default: return false;
            }
        }

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::scCompare($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function scEquals(ScalarCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $a === $b;

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::scEquals($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function scGt(ScalarCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $c->a > $c->b;

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::scGt($c[$i]) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function scGte(ScalarCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $a >= $b;

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::scGte($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function scLt(ScalarCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $a < $b;

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::scLt($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }

    public static function scLte(ScalarCondition | array $c, mixed $a, mixed $b): bool
    {
        if (!is_array($c)) return $a <= $b;

        $cond = true;
        $l = count($c);
        $i = 0;
        do {
            $cond = static::scLte($c[$i], $a, $b) && $cond;
            $i++;
        } while($i<$l && $cond);

        return $cond;
    }
}

interface ScalarComparable
{
    public static function scCompare(ScalarCondition | array $c, mixed $a, mixed $b): bool;
    public static function scEquals(ScalarCondition | array $c, mixed $a, mixed $b): bool;
    public static function scGt(ScalarCondition | array $c, mixed $a, mixed $b): bool;
    public static function scGte(ScalarCondition | array $c, mixed $a, mixed $b): bool;
    public static function scLt(ScalarCondition | array $c, mixed $a, mixed $b): bool;
    public static function scLte(ScalarCondition | array $c, mixed $a, mixed $b): bool;
}

interface PropertyComparable
{
    public static function propCompare(PropertyCondition | array $c, mixed $a, mixed $b): bool;
    public static function propEquals(PropertyCondition | array $c, mixed $a, mixed $b): bool;
    public static function propGt(PropertyCondition | array $c, mixed $a, mixed $b): bool;
    public static function propGte(PropertyCondition | array $c, mixed $a, mixed $b): bool;
    public static function propLt(PropertyCondition | array $c, mixed $a, mixed $b): bool;
    public static function propLte(PropertyCondition | array $c, mixed $a, mixed $b): bool;
}

class ScalarCondition
{
    public function __construct(public string $o) {}
}

class PropertyCondition
{
    public function __construct(public string $o, public string $property) {}
}

class Operator
{
    const EQUAL_TO = '===';
    const GREATER_THAN = '>';
    const GREATER_OR_EQUAL_TO = '>=';
    const LESSER_THAN = '<';
    const LESSER_OR_EQUAL_TO = '<=';
}
