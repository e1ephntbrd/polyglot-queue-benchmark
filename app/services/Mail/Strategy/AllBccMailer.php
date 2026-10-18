<?php

namespace app\services\Mail\Strategy;

/**
 * Strategy: Send one email with all recipients in BCC.
 */
class AllBccMailer extends MailerStrategyAbstract implements MailerStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function send(string $from, string $subject, array $emails): bool
    {
        return $this->mailer
            ->setFrom($from)
            ->setTo('receiver@example.local')
            ->setBcc($emails) // <--- Using blind copy just because Mailhog tests (1 received mail means ~1-5k copies also were sent)
            ->setSubject($subject)
            ->send();
    }
}
