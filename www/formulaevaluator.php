<?php

declare(strict_types=1);

enum TokenType: string
{
    case Number = 'NUMBER';
    case Ident = 'IDENT';
    case LParen = 'LPAREN';
    case RParen = 'RPAREN';
    case Semi = 'SEMI';
    case Comma = 'COMMA';
    case Op = 'OP';
}

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $value,
    ) {}
}

final class FormulaLexer
{
    /** @return list<Token> */
    public function tokenize(string $input): array
    {
        $expr = ltrim(trim($input), '=');
        $tokens = [];
        $len = strlen($expr);
        $i = 0;

        while ($i < $len) {
            $ch = $expr[$i];

            if (ctype_space($ch)) {
                $i++;
                continue;
            }

            if (ctype_digit($ch) || ($ch === '.' && $i + 1 < $len && ctype_digit($expr[$i + 1]))) {
                $start = $i;
                while ($i < $len && (ctype_digit($expr[$i]) || $expr[$i] === '.')) {
                    $i++;
                }
                $tokens[] = new Token(TokenType::Number, substr($expr, $start, $i - $start));
                continue;
            }

            if (ctype_alpha($ch) || $ch === '_') {
                $start = $i;
                while ($i < $len && (ctype_alnum($expr[$i]) || $expr[$i] === '_')) {
                    $i++;
                }
                $tokens[] = new Token(TokenType::Ident, strtoupper(substr($expr, $start, $i - $start)));
                continue;
            }

            $twoChar = substr($expr, $i, 2);
            if (in_array($twoChar, ['>=', '<=', '<>', '!='], true)) {
                $tokens[] = new Token(TokenType::Op, $twoChar);
                $i += 2;
                continue;
            }

            if (in_array($ch, ['+', '-', '*', '/', '^', '>', '<', '='], true)) {
                $tokens[] = new Token(TokenType::Op, $ch);
                $i++;
                continue;
            }

            switch ($ch) {
                case '(':
                    $tokens[] = new Token(TokenType::LParen, '(');
                    break;
                case ')':
                    $tokens[] = new Token(TokenType::RParen, ')');
                    break;
                case ';':
                    $tokens[] = new Token(TokenType::Semi, ';');
                    break;
                case ',':
                    $tokens[] = new Token(TokenType::Comma, ',');
                    break;
                default:
                    throw new InvalidArgumentException("Caractere inesperado: '{$ch}'");
            }
            $i++;
        }

        return $tokens;
    }
}

final class FormulaEvaluator
{
    private int $pos = 0;

    /** @param list<Token> $tokens */
    public function __construct(
        private array $tokens,
        /** @var array<string, float|int> */
        private array $variables,
    ) {
        $this->variables = array_change_key_case($this->variables, CASE_UPPER);
    }

    public function evaluate(): float|int
    {
        $this->pos = 0;
        $result = $this->parseComparison();

        if ($this->pos < count($this->tokens)) {
            throw new InvalidArgumentException('Tokens sobrando na expressão.');
        }

        return is_bool($result) ? ($result ? 1 : 0) : $result;
    }

    private function peek(): ?Token
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function consume(): Token
    {
        $token = $this->peek();
        if ($token === null) {
            throw new InvalidArgumentException('Fim inesperado da fórmula.');
        }
        $this->pos++;
        return $token;
    }

    private function isArgSep(?Token $token): bool
    {
        return $token !== null && ($token->type === TokenType::Semi || $token->type === TokenType::Comma);
    }

    private function parseComparison(): float|int|bool
    {
        $left = $this->parseAddSub();

        while (($token = $this->peek()) !== null && $token->type === TokenType::Op && in_array($token->value, ['=', '<>', '!=', '>', '<', '>=', '<='], true)) {
            $op = $this->consume()->value;
            $right = $this->parseAddSub();
            $left = match ($op) {
                '=' => (float)$left === (float)$right,
                '<>', '!=' => (float)$left !== (float)$right,
                '>' => (float)$left > (float)$right,
                '<' => (float)$left < (float)$right,
                '>=' => (float)$left >= (float)$right,
                '<=' => (float)$left <= (float)$right,
            };
        }

        return $left;
    }

    private function parseAddSub(): float|int|bool
    {
        $left = $this->parseMulDiv();

        while (($token = $this->peek()) !== null && $token->type === TokenType::Op && in_array($token->value, ['+', '-'], true)) {
            $op = $this->consume()->value;
            $right = $this->parseMulDiv();
            $left = $op === '+' ? (float)$left + (float)$right : (float)$left - (float)$right;
        }

        return $left;
    }

    private function parseMulDiv(): float|int|bool
    {
        $left = $this->parsePower();

        while (($token = $this->peek()) !== null && $token->type === TokenType::Op && in_array($token->value, ['*', '/'], true)) {
            $op = $this->consume()->value;
            $right = $this->parsePower();
            if ($op === '/') {
                if ((float)$right === 0.0) {
                    throw new DivisionByZeroError('Divisão por zero na fórmula.');
                }
                $left = (float)$left / (float)$right;
            } else {
                $left = (float)$left * (float)$right;
            }
        }

        return $left;
    }

    private function parsePower(): float|int|bool
    {
        $base = $this->parseUnary();

        if (($token = $this->peek()) !== null && $token->type === TokenType::Op && $token->value === '^') {
            $this->consume();
            $exp = $this->parsePower();
            return (float)$base ** (float)$exp;
        }

        return $base;
    }

    private function parseUnary(): float|int|bool
    {
        $token = $this->peek();
        if ($token !== null && $token->type === TokenType::Op && in_array($token->value, ['+', '-'], true)) {
            $op = $this->consume()->value;
            $val = $this->parseUnary();
            return $op === '-' ? -(float)$val : (float)$val;
        }

        return $this->parsePrimary();
    }

    private function parsePrimary(): float|int|bool
    {
        $token = $this->consume();

        if ($token->type === TokenType::Number) {
            return str_contains($token->value, '.') ? (float)$token->value : (int)$token->value;
        }

        if ($token->type === TokenType::LParen) {
            $val = $this->parseComparison();
            $close = $this->consume();
            if ($close->type !== TokenType::RParen) {
                throw new InvalidArgumentException("Esperado ')' mas encontrado '{$close->value}'.");
            }
            return $val;
        }

        if ($token->type === TokenType::Ident) {
            $next = $this->peek();
            if ($next !== null && $next->type === TokenType::LParen) {
                return $this->callFunction($token->value);
            }

            if (!array_key_exists($token->value, $this->variables)) {
                throw new InvalidArgumentException("Variável desconhecida: '{$token->value}'.");
            }

            return $this->variables[$token->value];
        }

        throw new InvalidArgumentException("Token inesperado: '{$token->value}'.");
    }

    private function callFunction(string $name): float|int|bool
    {
        $this->consume(); // consome '('
        $args = [];

        if (($token = $this->peek()) !== null && $token->type !== TokenType::RParen) {
            do {
                $args[] = $this->parseComparison();
            } while ($this->isArgSep($this->peek()) && $this->consume());
        }

        $close = $this->consume();
        if ($close->type !== TokenType::RParen) {
            throw new InvalidArgumentException("Esperado ')' ao fechar função {$name}.");
        }

        return match ($name) {
            'SE', 'IF' => $this->fnIf($args),
            'OU', 'OR' => $this->fnOr($args),
            'E', 'AND' => $this->fnAnd($args),
            'NAO', 'NOT' => $this->fnNot($args),
            'MAX' => $this->fnMax($args),
            'MIN' => $this->fnMin($args),
            'MEDIA', 'AVG' => $this->fnAvg($args),
            'ARRED', 'ROUND' => $this->fnRound($args),
            default => throw new InvalidArgumentException("Função não suportada: '{$name}'."),
        };
    }

    /** @param list<float|int|bool> $args */
    private function fnIf(array $args): float|int|bool
    {
        if (count($args) !== 3) {
            throw new InvalidArgumentException('SE exige exatamente 3 argumentos: SE(condicao; se_verdadeiro; se_falso).');
        }
        return (bool)$args[0] ? $args[1] : $args[2];
    }

    /** @param list<float|int|bool> $args */
    private function fnOr(array $args): bool
    {
        if (count($args) < 1) {
            throw new InvalidArgumentException('OU exige pelo menos 1 argumento.');
        }
        foreach ($args as $arg) {
            if ((bool)$arg) {
                return true;
            }
        }
        return false;
    }

    /** @param list<float|int|bool> $args */
    private function fnAnd(array $args): bool
    {
        if (count($args) < 1) {
            throw new InvalidArgumentException('E exige pelo menos 1 argumento.');
        }
        foreach ($args as $arg) {
            if (!(bool)$arg) {
                return false;
            }
        }
        return true;
    }

    /** @param list<float|int|bool> $args */
    private function fnNot(array $args): bool
    {
        if (count($args) !== 1) {
            throw new InvalidArgumentException('NAO exige exatamente 1 argumento.');
        }
        return !(bool)$args[0];
    }

    /** @param list<float|int|bool> $args */
    private function fnMax(array $args): float
    {
        if (count($args) < 1) {
            throw new InvalidArgumentException('MAX exige pelo menos 1 argumento.');
        }
        return max(array_map(static fn($v): float => (float)$v, $args));
    }

    /** @param list<float|int|bool> $args */
    private function fnMin(array $args): float
    {
        if (count($args) < 1) {
            throw new InvalidArgumentException('MIN exige pelo menos 1 argumento.');
        }
        return min(array_map(static fn($v): float => (float)$v, $args));
    }

    /** @param list<float|int|bool> $args */
    private function fnAvg(array $args): float
    {
        if (count($args) < 1) {
            throw new InvalidArgumentException('MEDIA exige pelo menos 1 argumento.');
        }
        $nums = array_map(static fn($v): float => (float)$v, $args);
        return array_sum($nums) / count($nums);
    }

    /** @param list<float|int|bool> $args */
    private function fnRound(array $args): float
    {
        if (count($args) < 1 || count($args) > 2) {
            throw new InvalidArgumentException('ARRED exige 1 ou 2 argumentos.');
        }
        $precision = isset($args[1]) ? (int)$args[1] : 0;
        return round((float)$args[0], $precision);
    }
}


/*
$formula = '=SE(OU(MOSS > 40; IA > 80); 0; MAX(NOTA; NOTA_LLM))';
$lexer = new FormulaLexer();
$tokens = $lexer->tokenize($formula);

$evaluator = new FormulaEvaluator($tokens, [
    'nota' => 7.5,
    'nota_llm' => 8.5,
    'moss' => 25,
    'ia' => 50,
]);

echo $evaluator->evaluate(); // 8.5
*/