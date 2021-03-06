<?php

namespace React\Promise;

final class RejectedPromise implements PromiseInterface
{
    private $reason;

    public function __construct(\Throwable $reason)
    {
        $this->reason = $reason;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        if (null === $onRejected) {
            return $this;
        }

        return new Promise(function (callable $resolve, callable $reject) use ($onRejected) {
            enqueue(function () use ($resolve, $reject, $onRejected) {
                try {
                    $resolve($onRejected($this->reason));
                } catch (\Throwable $exception) {
                    $reject($exception);
                }
            });
        });
    }

    public function done(callable $onFulfilled = null, callable $onRejected = null)
    {
        enqueue(function () use ($onRejected) {
            if (null === $onRejected) {
                return fatalError($this->reason);
            }

            try {
                $result = $onRejected($this->reason);
            } catch (\Throwable $exception) {
                return fatalError($exception);
            }

            if ($result instanceof self) {
                return fatalError($result->reason);
            }

            if ($result instanceof PromiseInterface) {
                $result->done();
            }
        });
    }

    public function otherwise(callable $onRejected)
    {
        if (!_checkTypehint($onRejected, $this->reason)) {
            return $this;
        }

        return $this->then(null, $onRejected);
    }

    public function always(callable $onFulfilledOrRejected)
    {
        return $this->then(null, function (\Throwable $reason) use ($onFulfilledOrRejected) {
            return resolve($onFulfilledOrRejected())->then(function () use ($reason) {
                return new RejectedPromise($reason);
            });
        });
    }

    public function cancel()
    {
    }
}
