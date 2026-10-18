<?php

namespace app\services\Mail\Strategy;

/**
 * Strategy: Send one email with all recipients in CC.
 */
class AllCcMailer extends MailerStrategyAbstract implements MailerStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function send(string $from, string $subject, array $emails): bool
    {
        return $this->mailer
            ->setFrom($from)
            ->setTo($emails)
            ->setSubject($subject)
            ->send();
    }
}
