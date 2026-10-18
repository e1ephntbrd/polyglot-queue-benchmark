<?php

namespace app\services\Mail\Strategy;

use yii\mail\MessageInterface;

/**
 * Mailer Strategy Abstraction
 */
abstract class MailerStrategyAbstract implements MailerStrategyInterface
{
    /**
     * @param MessageInterface $mailer
     */
    public function __construct(
        protected readonly MessageInterface $mailer
    )
    {
    }

    /**
     * @inheritDoc
     */
    abstract public function send(string $from, string $subject, array $emails): bool;
}
