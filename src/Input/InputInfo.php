<?php declare(strict_types=1);

namespace SouthPointe\Cli\Input;

final class InputInfo
{
    public string $prompt;
    public string $buffer = '';
    public string $latest = '';
    public string $clipboard = '';
    public int $point = 0;
    public int $end = 0;
    public bool $done = false;

    /**
     * @param string|null $prompt
     */
    public function __construct(?string $prompt)
    {
        $this->prompt = $prompt ?? '';
    }
}
