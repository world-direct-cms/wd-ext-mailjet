<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Transport decorator that catches exceptions during email sending
 * and makes them available for logging before re-throwing them
 */
final class LoggingTransportDecorator implements TransportInterface
{
    /**
     * Storage for the last exception that occurred during send
     */
    private static ?\Throwable $lastException = null;

    public function __construct(
        private readonly TransportInterface $innerTransport
    ) {}

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        // Clear previous exception
        self::$lastException = null;

        try {
            return $this->innerTransport->send($message, $envelope);
        } catch (\Throwable $e) {
            // Store the exception for logging
            self::$lastException = $e;

            // Re-throw so normal error handling continues
            throw $e;
        }
    }

    public function __toString(): string
    {
        return $this->innerTransport->__toString();
    }

    /**
     * Get the last exception that occurred during send
     */
    public static function getLastException(): ?\Throwable
    {
        return self::$lastException;
    }

    /**
     * Clear stored exception
     */
    public static function clear(): void
    {
        self::$lastException = null;
    }
}
