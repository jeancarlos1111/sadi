<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Motor de evaluación seguro de expresiones matemáticas para la Nómina.
 *
 * Soporta: +, -, *, /, paréntesis, números y variables de contexto.
 * NO usa eval() de PHP. Implementa un tokenizador + Shunting-yard + evaluador de pila.
 *
 * Variables disponibles en fórmulas:
 *   SUELDO          - Sueldo básico del trabajador
 *   TOTAL_ASIG      - Total de asignaciones acumuladas (útil para calcular deducciones)
 *   SALARIO_MINIMO  - Salario mínimo (variable de configuración global)
 *   CESTATICKET     - Monto del cestaticket (variable de configuración global)
 */
class FormulaEvaluator
{
    /** @var array<string, float> */
    private array $variables = [];

    /** @var string[] Variables permitidas en fórmulas */
    private const ALLOWED_VARS = ['SUELDO', 'TOTAL_ASIG', 'SALARIO_MINIMO', 'CESTATICKET'];

    public function setVariable(string $name, float $value): self
    {
        $this->variables[strtoupper($name)] = $value;

        return $this;
    }

    public function setVariables(array $vars): self
    {
        foreach ($vars as $name => $value) {
            $this->setVariable($name, (float)$value);
        }

        return $this;
    }

    /**
     * Evalúa una expresión matemática de forma segura.
     *
     * @throws InvalidArgumentException si la expresión contiene tokens inválidos
     */
    public function evaluate(string $expression): float
    {
        // Sustituir variables por sus valores
        $expr = strtoupper(trim($expression));

        foreach (self::ALLOWED_VARS as $var) {
            $value = $this->variables[$var] ?? 0.0;
            $expr = str_replace($var, (string)$value, $expr);
        }

        // Tokenizar
        $tokens = $this->tokenize($expr);

        // Algoritmo Shunting-yard → Notación Polaca Inversa (RPN)
        $rpn = $this->toRPN($tokens);

        // Evaluar RPN con pila
        return $this->evalRPN($rpn);
    }

    /**
     * Convierte la expresión en un array de tokens: números y operadores.
     */
    private function tokenize(string $expr): array
    {
        $tokens = [];
        $len = strlen($expr);
        $i = 0;

        while ($i < $len) {
            $ch = $expr[$i];

            // Saltar espacios
            if ($ch === ' ') {
                $i++;

                continue;
            }

            // Número (entero o decimal)
            if (is_numeric($ch) || ($ch === '.' && $i + 1 < $len && is_numeric($expr[$i + 1]))) {
                $num = '';
                while ($i < $len && (is_numeric($expr[$i]) || $expr[$i] === '.')) {
                    $num .= $expr[$i++];
                }
                $tokens[] = ['type' => 'NUMBER', 'value' => (float)$num];

                continue;
            }

            // Operadores y paréntesis
            if (in_array($ch, ['+', '-', '*', '/', '(', ')'], true)) {
                // Soporte al negativo unario: si '-' va al inicio o tras operador/apertura
                if ($ch === '-' && (empty($tokens) || in_array(end($tokens)['type'] ?? '', ['OP', 'LPAREN']))) {
                    // Tratar como negativo unario: leer el número que sigue
                    $i++;
                    $num = '-';
                    while ($i < $len && (is_numeric($expr[$i]) || $expr[$i] === '.')) {
                        $num .= $expr[$i++];
                    }
                    $tokens[] = ['type' => 'NUMBER', 'value' => (float)$num];

                    continue;
                }
                $type = ($ch === '(') ? 'LPAREN' : (($ch === ')') ? 'RPAREN' : 'OP');
                $tokens[] = ['type' => $type, 'value' => $ch];
                $i++;

                continue;
            }

            // Carácter inesperado — podría ser una variable no sustituida
            if (ctype_alpha($ch) || $ch === '_') {
                $word = '';
                while ($i < $len && (ctype_alnum($expr[$i]) || $expr[$i] === '_')) {
                    $word .= $expr[$i++];
                }

                throw new InvalidArgumentException("Variable o símbolo desconocido en la fórmula: '{$word}'. Variables válidas: " . implode(', ', self::ALLOWED_VARS));
            }

            throw new InvalidArgumentException("Carácter no permitido en fórmula: '{$ch}'");
        }

        return $tokens;
    }

    /**
     * Convierte tokens en Reverse Polish Notation usando Shunting-yard.
     */
    private function toRPN(array $tokens): array
    {
        $output = [];
        $opStack = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        foreach ($tokens as $tok) {
            if ($tok['type'] === 'NUMBER') {
                $output[] = $tok;
            } elseif ($tok['type'] === 'OP') {
                $op = $tok['value'];
                while (
                    !empty($opStack) &&
                    end($opStack)['type'] === 'OP' &&
                    ($precedence[end($opStack)['value']] ?? 0) >= ($precedence[$op] ?? 0)
                ) {
                    $output[] = array_pop($opStack);
                }
                $opStack[] = $tok;
            } elseif ($tok['type'] === 'LPAREN') {
                $opStack[] = $tok;
            } elseif ($tok['type'] === 'RPAREN') {
                while (!empty($opStack) && end($opStack)['type'] !== 'LPAREN') {
                    $output[] = array_pop($opStack);
                }
                if (empty($opStack)) {
                    throw new InvalidArgumentException("Paréntesis desbalanceados en la fórmula.");
                }
                array_pop($opStack); // quitar el LPAREN
            }
        }

        while (!empty($opStack)) {
            $top = array_pop($opStack);
            if ($top['type'] === 'LPAREN') {
                throw new InvalidArgumentException("Paréntesis desbalanceados en la fórmula.");
            }
            $output[] = $top;
        }

        return $output;
    }

    /**
     * Evalúa la notación RPN y retorna el resultado.
     */
    private function evalRPN(array $rpn): float
    {
        $stack = [];

        foreach ($rpn as $tok) {
            if ($tok['type'] === 'NUMBER') {
                $stack[] = $tok['value'];
            } elseif ($tok['type'] === 'OP') {
                if (count($stack) < 2) {
                    throw new InvalidArgumentException("Fórmula mal formada: operandos insuficientes.");
                }
                $b = array_pop($stack);
                $a = array_pop($stack);
                switch ($tok['value']) {
                    case '+': $stack[] = $a + $b;

                        break;
                    case '-': $stack[] = $a - $b;

                        break;
                    case '*': $stack[] = $a * $b;

                        break;
                    case '/':
                        if ($b == 0) {
                            throw new InvalidArgumentException("División por cero en fórmula.");
                        }
                        $stack[] = $a / $b;

                        break;
                }
            }
        }

        if (count($stack) !== 1) {
            throw new InvalidArgumentException("Fórmula mal formada: resultado ambiguo.");
        }

        return (float)array_pop($stack);
    }

    /**
     * Valida una expresión sin evaluarla completamente (solo tokeniza).
     * @return string|null Mensaje de error o null si es válida.
     */
    public static function validate(string $expression): ?string
    {
        try {
            $ev = new self();
            // Rellenar con ceros para validar la estructura
            foreach (self::ALLOWED_VARS as $v) {
                $ev->setVariable($v, 0.0);
            }
            $ev->evaluate($expression);

            return null;
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }
    }
}
