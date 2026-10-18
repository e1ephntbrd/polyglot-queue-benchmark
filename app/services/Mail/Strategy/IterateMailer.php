<?php

namespace app\services\Mail\Strategy;

/**
 * Strategy: Iterate over recipients and send one email per address.
 */
class IterateMailer extends MailerStrategyAbstract implements MailerStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function send(string $from, string $subject, array $emails): bool
    {
        foreach ($emails as $email) {
            $this->mailer
                ->setFrom($from)
                ->setTo($email)
                ->setSubject($subject)
                ->send();
        }
        return true;
    }
}
