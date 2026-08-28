<?php

namespace App\Enums;

enum GateType: string
{
    case Input = 'input';
    case Output = 'output';
    case And = 'and';
    case Or = 'or';
    case Not = 'not';
    case Xor = 'xor';
    case Nor = 'nor';
    case Nand = 'nand';

    public function inputCount(): int
    {
        return match ($this) {
            self::Input => 0,
            self::Not, self::Output => 1,
            default => 2,
        };
    }

    public function outputCount(): int
    {
        return match ($this) {
            self::Output => 0,
            default => 1,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Input => 'IN',
            self::Output => 'OUT',
            self::And => 'AND',
            self::Or => 'OR',
            self::Not => 'NOT',
            self::Xor => 'XOR',
            self::Nor => 'NOR',
            self::Nand => 'NAND',
        };
    }
}
