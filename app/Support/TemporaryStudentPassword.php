<?php

namespace App\Support;

class TemporaryStudentPassword
{
    private const UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    private const LOWER = 'abcdefghijkmnopqrstuvwxyz';
    private const NUMBERS = '23456789';

    public function generate(): string
    {
        $characters = [
            $this->pick(self::UPPER),
            $this->pick(self::LOWER),
            $this->pick(self::NUMBERS),
        ];
        $pool = self::UPPER.self::LOWER.self::NUMBERS;

        while (count($characters) < 12) {
            $characters[] = $this->pick($pool);
        }

        for ($index = count($characters) - 1; $index > 0; $index--) {
            $swap = random_int(0, $index);
            [$characters[$index], $characters[$swap]] = [$characters[$swap], $characters[$index]];
        }

        return implode('', array_slice($characters, 0, 4)).'-'
            .implode('', array_slice($characters, 4, 4)).'-'
            .implode('', array_slice($characters, 8, 4));
    }

    private function pick(string $characters): string
    {
        return $characters[random_int(0, strlen($characters) - 1)];
    }
}
