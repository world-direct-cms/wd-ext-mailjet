<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\Utility;

/**
 * Utility for extracting caller information
 */
class CallerUtility
{
    /**
     * Extract the calling class that initiated the email sending
     * This searches through the stack trace to find the first non-TYPO3-core class
     */
    public static function getCallingClass(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Skip TYPO3 core classes and find the actual application class that sent the email
        foreach ($trace as $frame) {
            if (!isset($frame['class'])) {
                continue;
            }

            $class = $frame['class'];

            // Skip TYPO3 core classes, Symfony components, and this extension's classes
            if (
                str_starts_with($class, 'TYPO3\\CMS\\') ||
                str_starts_with($class, 'Symfony\\') ||
                str_starts_with($class, 'WorldDirect\\Mailjet\\')
            ) {
                continue;
            }

            // Return the first non-core class found
            return $class . '::' . ($frame['function'] ?? 'unknown');
        }

        // Check if this is from Install Tool or Backend modules
        foreach ($trace as $frame) {
            if (isset($frame['class'])) {
                $class = $frame['class'];

                // Identify Install Tool test emails
                if (str_contains($class, 'Install\\Controller')) {
                    return 'TYPO3 Install Tool::mailTest';
                }

                // Identify backend module emails
                if (str_contains($class, 'Backend\\Controller')) {
                    return 'TYPO3 Backend::' . ($frame['function'] ?? 'unknown');
                }
            }
        }

        return 'System/Unknown';
    }
}
