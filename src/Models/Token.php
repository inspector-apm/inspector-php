<?php

declare(strict_types=1);

namespace Inspector\Models;

class Token extends Model
{
    public string $model = 'token';
    public int $input_tokens;
    public int $output_tokens;
    public string $agent;
    public ?array $transaction = null;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction->only(['name', 'hash', 'timestamp']);
    }

    public function setInputTokens(int $input_tokens): Token
    {
        $this->input_tokens = $input_tokens;
        return $this;
    }

    public function setOutputTokens(int $output_tokens): Token
    {
        $this->output_tokens = $output_tokens;
        return $this;
    }

    public function setAgent(string $agent): Token
    {
        $this->agent = $agent;
        return $this;
    }
}
