<?php

namespace app\services\Mail\Strategy;

/**
 * Interface for mailer strategies.
 *
 * Defines a contract for different ways of sending emails.
 */
interface MailerStrategyInterface
{
    /**
     * Send emails using a specific strategy.
     *
     * @param string $from Email sender
     * @param string $subject Email subject
     * @param string[] $emails List of recipient email addresses
     * @return bool
     */
    public function send(string $from, string $subject, array $emails): bool;
}
