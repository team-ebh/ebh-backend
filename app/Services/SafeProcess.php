<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

use function Sentry\captureException;

class SafeProcess
{
    private mixed $onFailedProcess;

    private bool $report = false;

    private bool $transaction = false;

    private mixed $finally = null;

    private mixed $afterCommit = null;

    private int $attempts = 1;

    public function report(): static
    {
        $this->report = true;

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function do(callable | array $process, mixed ...$args)
    {
        $this->validateProcessIfInArraySyntax($process);

        for ($attemptCount = 1; $attemptCount <= $this->attempts; $attemptCount++) {
            try {
                $this->transaction && DB::beginTransaction();
                $result = $process(...$args);
                $this->transaction && DB::commit();
                is_callable($this->afterCommit) && call_user_func($this->afterCommit);

                return $result;
            } catch (Throwable $throwableException) {
                $this->transaction && DB::transactionLevel() && DB::rollBack();

                if ($this->shouldItBeRetriedAgain($attemptCount)) {
                    continue;
                }

                if ($this->report && function_exists('Sentry\captureException')) {
                    captureException($throwableException);
                }

                return $this->doOnFailedActionIfExists($throwableException);
            } finally {
                is_callable($this->finally) && call_user_func($this->finally);
            }
        }
    }

    public function afterCommit(callable $afterCommitProcess): static
    {
        $this->afterCommit = $afterCommitProcess;

        return $this;
    }

    public function onFailed(array | callable $onFailedProcessResult): static
    {
        $this->validateProcessIfInArraySyntax($onFailedProcessResult);

        $this->onFailedProcess = $onFailedProcessResult;

        return $this;
    }

    public function withTransaction(): static
    {
        $this->transaction = true;

        return $this;
    }

    public function onFinally(callable $finallyCallback): static
    {
        $this->finally = $finallyCallback;

        return $this;
    }

    /**
     * @throws Throwable
     */
    private function doOnFailedActionIfExists(Throwable | Exception $throwableException): mixed
    {
        if ($this->onFailedProcess ?? null) {
            return call_user_func($this->onFailedProcess, $throwableException);
        }

        return throw $throwableException;
    }

    private function validateProcessIfInArraySyntax(callable | array $process): void
    {
        // validate the callback (when passed in array form like [$this, 'methodName']
        if (is_array($process) && ! method_exists($process[0], $process[1])) {
            throw new InvalidArgumentException('Your Process is not valid callback!');
        }
    }

    public function shouldItBeRetriedAgain(mixed $attemptCount): bool
    {
        return $attemptCount !== $this->attempts;
    }

    public function setAttemptCount(int $attempts = 3): static
    {
        $this->attempts = $attempts;

        return $this;
    }
}
