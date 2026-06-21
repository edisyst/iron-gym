<?php

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('dispatch di un job viene registrato nella coda', function (): void {
    Queue::fake();

    $job = new class implements ShouldQueue
    {
        use InteractsWithQueue;
        use Queueable;

        public function handle(): void
        {
            throw new RuntimeException('Eccezione di test per Flare.');
        }
    };

    dispatch($job);

    Queue::assertPushed($job::class);
});

it('le eccezioni di validazione non vengono segnalate a Flare', function (): void {
    $handler = app(ExceptionHandler::class);

    $exception = ValidationException::withMessages(['campo' => 'obbligatorio']);

    expect(fn () => $handler->report($exception))->not->toThrow(Throwable::class);
});
