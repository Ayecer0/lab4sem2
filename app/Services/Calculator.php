<?php

namespace App\Services;

use InvalidArgumentException;

class Calculator
{
    private const MAX_LENGTH = 255;
    private const NAMES = ['pi', 'e', 'sqrt', 'ln', 'log'];

    private array $tokens = [];
    private int $pos = 0;

    
    public function evaluate(string $expression): string
    {
        $expression = preg_replace('/\s+/', '', strtolower($expression)) ?? '';

        if ($expression === '') {
            throw new InvalidArgumentException('Введите выражение');
        }
        if (strlen($expression) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Выражение слишком длинное');
        }

        $this->tokens = $this->tokenize($expression);
        $this->pos = 0;

        $value = $this->parseExpression();

        if ($this->pos < count($this->tokens)) {
            $text = $this->tokens[$this->pos]['text'];
            throw new InvalidArgumentException(
                $text === ')' ? 'Лишняя закрывающая скобка' : "Неожиданный символ: $text"
            );
        }

        return $this->format($value);
    }

   

    private function tokenize(string $s): array
    {
        $tokens = [];
        $i = 0;
        $len = strlen($s);

        while ($i < $len) {
            if (preg_match('/\G(\d+\.\d+|\d+|\.\d+)/', $s, $m, 0, $i)) {
                $tokens[] = ['type' => 'num', 'text' => $m[1]];
                $i += strlen($m[1]);
            } elseif (preg_match('/\G[a-z]+/', $s, $m, 0, $i)) {
                if (!in_array($m[0], self::NAMES, true)) {
                    throw new InvalidArgumentException("Неизвестное имя: {$m[0]}");
                }
                $tokens[] = ['type' => 'name', 'text' => $m[0]];
                $i += strlen($m[0]);
            } elseif (strpos('+-*/^()!', $s[$i]) !== false) {
                $tokens[] = ['type' => 'op', 'text' => $s[$i]];
                $i++;
            } else {
                throw new InvalidArgumentException("Недопустимый символ: {$s[$i]}");
            }
        }

        return $tokens;
    }

    

    private function peekOp(): ?string
    {
        $t = $this->tokens[$this->pos] ?? null;
        return ($t !== null && $t['type'] === 'op') ? $t['text'] : null;
    }

    private function parseExpression(): float
    {
        $left = $this->parseTerm();

        while (($op = $this->peekOp()) === '+' || $op === '-') {
            $this->pos++;
            $right = $this->parseTerm();
            $left = $op === '+' ? $this->add($left, $right) : $this->subtract($left, $right);
        }

        return $left;
    }

    private function parseTerm(): float
    {
        $left = $this->parseUnary();

        while (($op = $this->peekOp()) === '*' || $op === '/') {
            $this->pos++;
            $right = $this->parseUnary();
            $left = $op === '*' ? $this->multiply($left, $right) : $this->divide($left, $right);
        }

        return $left;
    }

    private function parseUnary(): float
    {
        $op = $this->peekOp();

        if ($op === '-') {
            $this->pos++;
            return $this->negate($this->parseUnary()); // -(2+3), --2, -2^2 = -(2^2)
        }
        if ($op === '+') {
            $this->pos++;
            return $this->parseUnary();
        }

        return $this->parsePower();
    }

    private function parsePower(): float
    {
        $base = $this->parsePostfix();

        if ($this->peekOp() === '^') {
            $this->pos++;
            $exponent = $this->parseUnary(); 
            return $this->power($base, $exponent);
        }

        return $base;
    }

    private function parsePostfix(): float
    {
        $value = $this->parsePrimary();

        while ($this->peekOp() === '!') {
            $this->pos++;
            $value = $this->factorial($value);
        }

        return $value;
    }

    private function parsePrimary(): float
    {
        $token = $this->tokens[$this->pos] ?? null;

        if ($token === null) {
            throw new InvalidArgumentException('Выражение не закончено');
        }

        if ($token['type'] === 'num') {
            $this->pos++;
            return (float) $token['text'];
        }

        if ($token['type'] === 'op' && $token['text'] === '(') {
            $this->pos++;
            $value = $this->parseExpression();
            $this->expectClosingBracket();
            return $value;
        }

        if ($token['type'] === 'name') {
            $this->pos++;

            if ($token['text'] === 'pi') {
                return M_PI;
            }
            if ($token['text'] === 'e') {
                return M_E;
            }

            
            if ($this->peekOp() !== '(') {
                throw new InvalidArgumentException("После {$token['text']} нужна скобка");
            }
            $this->pos++;
            $argument = $this->parseExpression();
            $this->expectClosingBracket();

            return match ($token['text']) {
                'sqrt' => $this->root($argument),
                'ln'   => $this->naturalLog($argument),
                'log'  => $this->decimalLog($argument),
            };
        }

        throw new InvalidArgumentException("Неожиданный символ: {$token['text']}");
    }

    private function expectClosingBracket(): void
    {
        if ($this->peekOp() !== ')') {
            throw new InvalidArgumentException('Не закрыта скобка');
        }
        $this->pos++;
    }

    

    public function add(float $a, float $b): float
    {
        return $this->finite($a + $b);
    }

    public function subtract(float $a, float $b): float
    {
        return $this->finite($a - $b);
    }

    public function multiply(float $a, float $b): float
    {
        return $this->finite($a * $b);
    }

    public function divide(float $a, float $b): float
    {
        if ($b == 0) {
            throw new InvalidArgumentException('Деление на ноль');
        }
        return $this->finite($a / $b);
    }

    public function negate(float $a): float
    {
        return $this->multiply(-1, $a);
    }

    public function power(float $base, float $exponent): float
    {
        if ($base == 0 && $exponent < 0) {
            throw new InvalidArgumentException('Деление на ноль');
        }
        if ($base < 0 && floor($exponent) != $exponent) {
            throw new InvalidArgumentException('Нельзя возвести отрицательное число в дробную степень');
        }
        return $this->finite(pow($base, $exponent));
    }

    public function root(float $a): float
    {
        if ($a < 0) {
            throw new InvalidArgumentException('Корень из отрицательного числа не определён');
        }
        return sqrt($a);
    }

    public function naturalLog(float $a): float
    {
        if ($a <= 0) {
            throw new InvalidArgumentException('Логарифм определён только для положительных чисел');
        }
        return log($a);
    }

    public function decimalLog(float $a): float
    {
        if ($a <= 0) {
            throw new InvalidArgumentException('Логарифм определён только для положительных чисел');
        }
        return log10($a);
    }

    
    public function factorial(float $n): float
    {
        if ($n < 0 || floor($n) != $n) {
            throw new InvalidArgumentException('Факториал определён только для целых неотрицательных чисел');
        }
        if ($n > 170) {
            throw new InvalidArgumentException('Слишком большое число для факториала');
        }
        return $n <= 1 ? 1.0 : $this->multiply($n, $this->factorial($n - 1));
    }

    // ---------------------------------------------------------------

    private function finite(float $value): float
    {
        if (is_nan($value)) {
            throw new InvalidArgumentException('Результат не определён');
        }
        if (is_infinite($value)) {
            throw new InvalidArgumentException('Слишком большое число');
        }
        return $value;
    }

    private function format(float $value): string
    {
        $value = round($value, 10);

        if ($value == 0) {
            return '0';
        }
        if (abs($value) >= 1e15) {
            return (string) $value;
        }

        return rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');
    }
}
