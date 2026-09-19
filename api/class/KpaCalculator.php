<?php

/**
 * KPI achievement and APD calculator.
 *
 * Most indicators are plain arithmetic and are stored as an editable formula
 * string (calc_type EXPRESSION), evaluated by a small recursive-descent parser.
 * eval() is never used. Three indicators need behaviour that cannot be written
 * as one expression and have their own named calculator:
 *
 *   BACKLOG_AVG  PI 1E - average of the <30 / <60 / <90 day bucket achievements
 *   BEI          PI 3B - (electricity + chilled water) / floor area, compared
 *                        against a target BEI rather than a percentage
 *   AVG_PARAMS   PI 4C - mean of the supplied percentages
 */
class KpaCalculator {

    /** Raised when a formula cannot produce a number (missing input, /0, bad syntax). */
    private const UNAVAILABLE = 'KPA_UNAVAILABLE';

    private $tokens = array();
    private $pos = 0;
    private $values = array();

    /**
     * @param array $pi     snapshot row: calcType, formulaExpr, targetValue, passRule, targetUnit
     * @param array $params map of paramKey => value (null when not captured yet)
     * @return array {actualValue, resultPct, isPass, message}
     */
    public function evaluate(array $pi, array $params): array {
        $calcType = strtoupper(strval($pi['calcType'] ?? 'EXPRESSION'));
        $target = floatval($pi['targetValue'] ?? 0);
        $passRule = strtoupper(strval($pi['passRule'] ?? 'GTE_TARGET'));

        try {
            switch ($calcType) {
                case 'BACKLOG_AVG':
                    $actual = $this->backlogAverage($params);
                    break;
                case 'BEI':
                    $actual = $this->buildingEnergyIndex($params);
                    break;
                case 'AVG_PARAMS':
                    $actual = $this->averageParams($params);
                    break;
                case 'DIRECT':
                    $actual = $this->firstValue($params);
                    break;
                case 'EXPRESSION':
                default:
                    $expr = strval($pi['formulaExpr'] ?? '');
                    if (trim($expr) === '') {
                        return $this->unavailable('No formula is configured for this indicator.');
                    }
                    $actual = $this->expression($expr, $params);
                    break;
            }
        } catch (Exception $ex) {
            if ($ex->getMessage() === self::UNAVAILABLE) {
                return $this->unavailable('The achievement cannot be calculated yet. Check that every parameter is filled in and that no divisor is zero.');
            }
            return $this->unavailable($ex->getMessage());
        }

        if ($actual === null || !is_finite($actual)) {
            return $this->unavailable('The achievement cannot be calculated from the values entered.');
        }

        $actual = round($actual, 4);
        // Percentage indicators are reported as-is; BEI reports pass/fail as 100/0
        // so the summary can show one comparable column.
        $isBei = $calcType === 'BEI' || strtoupper(strval($pi['targetUnit'] ?? '%')) === 'BEI';
        $isPass = $this->passes($actual, $target, $passRule);
        $resultPct = $isBei ? ($isPass ? 100.0 : 0.0) : $actual;

        return array(
            'actualValue' => $actual,
            'resultPct' => round($resultPct, 4),
            'isPass' => $isPass,
            'message' => null
        );
    }

    public function passes(float $actual, float $target, string $passRule): bool {
        $epsilon = 0.00005;
        switch (strtoupper($passRule)) {
            case 'LTE_TARGET':
                return $actual <= $target + $epsilon;
            case 'EQ_TARGET':
                return abs($actual - $target) <= $epsilon;
            case 'GTE_TARGET':
            default:
                return $actual >= $target - $epsilon;
        }
    }

    /**
     * APD figures for one indicator.
     *
     *   apdMax   = MPV x maxApdPct%
     *   apdValue = apdMax x PI weightage%
     * A failing indicator loses its whole apdValue and takes its demerit points.
     */
    public function apd(float $apdMaxAmount, array $pi, ?bool $isPass): array {
        $weightage = floatval($pi['weightagePct'] ?? 0);
        $apdValue = round($apdMaxAmount * $weightage / 100, 2);
        if ($isPass === false) {
            return array(
                'apdValue' => $apdValue,
                'apdDeducted' => $apdValue,
                'demeritImposed' => intval($pi['demeritPoint'] ?? 0)
            );
        }
        return array('apdValue' => $apdValue, 'apdDeducted' => 0.00, 'demeritImposed' => 0);
    }

    /**
     * Validate a formula without storing anything. Used by the PI Definition
     * screen so an administrator can see the result before saving.
     */
    public function test(string $expr, array $params): array {
        try {
            $value = $this->expression($expr, $params);
            if ($value === null || !is_finite($value)) {
                return array('ok' => false, 'value' => null, 'message' => 'The formula did not produce a number.');
            }
            return array('ok' => true, 'value' => round($value, 4), 'message' => null);
        } catch (Exception $ex) {
            $message = $ex->getMessage() === self::UNAVAILABLE
                ? 'The formula cannot be calculated with these values (missing parameter or division by zero).'
                : $ex->getMessage();
            return array('ok' => false, 'value' => null, 'message' => $message);
        }
    }

    // -----------------------------------------------------------------------
    // Named calculators
    // -----------------------------------------------------------------------

    /**
     * PI 1E. Parameter pairs are (total, completed) for each ageing bucket.
     * A bucket with no backlog counts as fully achieved.
     */
    private function backlogAverage(array $params): ?float {
        $pairs = array(array('p1', 'p2'), array('p3', 'p4'), array('p5', 'p6'));
        $scores = array();
        foreach ($pairs as $pair) {
            $total = $this->value($params, $pair[0], true);
            $done = $this->value($params, $pair[1], true);
            if ($total === null && $done === null) {
                continue;
            }
            $total = $total ?? 0.0;
            $done = $done ?? 0.0;
            $scores[] = $total <= 0 ? 100.0 : ($done / $total) * 100;
        }
        if (empty($scores)) {
            throw new Exception(self::UNAVAILABLE);
        }
        return array_sum($scores) / count($scores);
    }

    /**
     * PI 3B. p1 electricity kWh, p2 chilled water kWh, p3 gross floor area and
     * an optional p4 annualisation factor.
     *
     * BEI is conventionally kWh/m2/year. Feeding one month of consumption gives
     * a monthly index, so p4 lets the site scale it (12 for monthly readings).
     * Leaving p4 blank keeps the plain total / floor area ratio.
     */
    private function buildingEnergyIndex(array $params): ?float {
        $electricity = $this->value($params, 'p1');
        $chilled = $this->value($params, 'p2', true);
        $floorArea = $this->value($params, 'p3');
        $factor = $this->value($params, 'p4', true);
        if ($electricity === null || $floorArea === null) {
            throw new Exception(self::UNAVAILABLE);
        }
        if ($floorArea <= 0) {
            throw new Exception('Enter a gross floor area greater than zero.');
        }
        if ($factor !== null && $factor <= 0) {
            throw new Exception('The annualisation factor must be greater than zero.');
        }
        $bei = ($electricity + ($chilled ?? 0.0)) / $floorArea;
        return $factor === null ? $bei : $bei * $factor;
    }

    /** PI 4C. Mean of every supplied percentage. */
    private function averageParams(array $params): ?float {
        $values = array();
        foreach ($params as $value) {
            if ($value !== null && $value !== '') {
                $values[] = floatval($value);
            }
        }
        if (empty($values)) {
            throw new Exception(self::UNAVAILABLE);
        }
        return array_sum($values) / count($values);
    }

    private function firstValue(array $params): ?float {
        foreach ($params as $value) {
            if ($value !== null && $value !== '') {
                return floatval($value);
            }
        }
        throw new Exception(self::UNAVAILABLE);
    }

    private function value(array $params, string $key, bool $optional = false): ?float {
        if (!array_key_exists($key, $params) || $params[$key] === null || $params[$key] === '') {
            if ($optional) {
                return null;
            }
            throw new Exception(self::UNAVAILABLE);
        }
        return floatval($params[$key]);
    }

    private function unavailable(string $message): array {
        return array('actualValue' => null, 'resultPct' => null, 'isPass' => null, 'message' => $message);
    }

    // -----------------------------------------------------------------------
    // Expression parser: numbers, pN parameters, + - * / ( ), min(), max()
    // -----------------------------------------------------------------------

    private function expression(string $expr, array $params): ?float {
        $this->tokens = $this->tokenize($expr);
        $this->pos = 0;
        $this->values = $params;
        $value = $this->parseSum();
        if ($this->pos < count($this->tokens)) {
            throw new Exception('The formula has unexpected text near "' . $this->tokens[$this->pos]['value'] . '".');
        }
        return $value;
    }

    private function tokenize(string $expr): array {
        $tokens = array();
        $len = strlen($expr);
        $i = 0;
        while ($i < $len) {
            $ch = $expr[$i];
            if (ctype_space($ch)) {
                $i++;
                continue;
            }
            if (strpos('+-*/(),', $ch) !== false) {
                $tokens[] = array('type' => $ch, 'value' => $ch);
                $i++;
                continue;
            }
            if (ctype_digit($ch) || $ch === '.') {
                $num = '';
                while ($i < $len && (ctype_digit($expr[$i]) || $expr[$i] === '.')) {
                    $num .= $expr[$i];
                    $i++;
                }
                if (!is_numeric($num)) {
                    throw new Exception('The formula contains an invalid number "' . $num . '".');
                }
                $tokens[] = array('type' => 'num', 'value' => $num);
                continue;
            }
            if (ctype_alpha($ch) || $ch === '_') {
                $name = '';
                while ($i < $len && (ctype_alnum($expr[$i]) || $expr[$i] === '_')) {
                    $name .= $expr[$i];
                    $i++;
                }
                $lower = strtolower($name);
                if ($lower === 'min' || $lower === 'max') {
                    $tokens[] = array('type' => 'func', 'value' => $lower);
                } else if (preg_match('/^p[0-9]+$/i', $name)) {
                    $tokens[] = array('type' => 'param', 'value' => strtolower($name));
                } else {
                    throw new Exception('The formula contains an unknown name "' . $name . '". Use p1, p2, min() or max().');
                }
                continue;
            }
            throw new Exception('The formula contains an unsupported character "' . $ch . '".');
        }
        if (empty($tokens)) {
            throw new Exception('The formula is empty.');
        }
        return $tokens;
    }

    private function peek(): ?array {
        return $this->tokens[$this->pos] ?? null;
    }

    private function parseSum(): float {
        $value = $this->parseProduct();
        while (($token = $this->peek()) !== null && ($token['type'] === '+' || $token['type'] === '-')) {
            $this->pos++;
            $right = $this->parseProduct();
            $value = $token['type'] === '+' ? $value + $right : $value - $right;
        }
        return $value;
    }

    private function parseProduct(): float {
        $value = $this->parseUnary();
        while (($token = $this->peek()) !== null && ($token['type'] === '*' || $token['type'] === '/')) {
            $this->pos++;
            $right = $this->parseUnary();
            if ($token['type'] === '/') {
                if (abs($right) < 1.0E-12) {
                    throw new Exception(self::UNAVAILABLE);
                }
                $value = $value / $right;
            } else {
                $value = $value * $right;
            }
        }
        return $value;
    }

    private function parseUnary(): float {
        $token = $this->peek();
        if ($token !== null && ($token['type'] === '-' || $token['type'] === '+')) {
            $this->pos++;
            $value = $this->parseUnary();
            return $token['type'] === '-' ? -$value : $value;
        }
        return $this->parsePrimary();
    }

    private function parsePrimary(): float {
        $token = $this->peek();
        if ($token === null) {
            throw new Exception('The formula ends unexpectedly.');
        }
        if ($token['type'] === 'num') {
            $this->pos++;
            return floatval($token['value']);
        }
        if ($token['type'] === 'param') {
            $this->pos++;
            $key = $token['value'];
            if (!array_key_exists($key, $this->values) || $this->values[$key] === null || $this->values[$key] === '') {
                throw new Exception(self::UNAVAILABLE);
            }
            return floatval($this->values[$key]);
        }
        if ($token['type'] === 'func') {
            $this->pos++;
            $this->expect('(');
            $args = array($this->parseSum());
            while (($next = $this->peek()) !== null && $next['type'] === ',') {
                $this->pos++;
                $args[] = $this->parseSum();
            }
            $this->expect(')');
            return $token['value'] === 'min' ? min($args) : max($args);
        }
        if ($token['type'] === '(') {
            $this->pos++;
            $value = $this->parseSum();
            $this->expect(')');
            return $value;
        }
        throw new Exception('The formula has unexpected text near "' . $token['value'] . '".');
    }

    private function expect(string $type): void {
        $token = $this->peek();
        if ($token === null || $token['type'] !== $type) {
            throw new Exception('The formula is missing a "' . $type . '".');
        }
        $this->pos++;
    }
}
