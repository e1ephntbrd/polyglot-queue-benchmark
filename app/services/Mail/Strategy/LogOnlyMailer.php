<?php

namespace app\services\Mail\Strategy;

/**
 * Strategy: Do not send emails, only log recipients.
 */
class LogOnlyMailer extends MailerStrategyAbstract implements MailerStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function send(string $from, string $subject, array $emails): bool
    {
        $emailsLogs = substr(implode(', ', $emails), 0, 100) . '....';
        \Yii::info(
            "Catch a new batch of emails: from={$from} subject={$subject} emails={$emailsLogs}",
            __METHOD__
        );
        return true;
    }
}
