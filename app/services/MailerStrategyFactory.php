<?php

namespace app\services;

use app\services\Mail\Strategy\AllBccMailer;
use app\services\Mail\Strategy\AllCcMailer;
use app\services\Mail\Strategy\IterateMailer;
use app\services\Mail\Strategy\LogOnlyMailer;
use app\services\Mail\Strategy\MailerStrategyInterface;
use yii\mail\MessageInterface;

/**
 * Factory for creating mailer strategies based on configuration.
 */
class MailerStrategyFactory
{
    /**#@+
     * Active strategies for mailer
     */
    public const ITERATE = 'iterate';
    public const ALL_CC = 'all_cc';
    public const ALL_BCC = 'all_bcc';
    public const LOG_ONLY = 'log_only';
    /**#@- */

    /**
     * @param MessageInterface $mailer
     */
    public function __construct(
        private readonly MessageInterface $mailer
    ) {
    }

    /**
     * Create a mailer strategy by name.
     *
     * @param string $strategyName Strategy identifier
     * @return MailerStrategyInterface
     */
    public function create(string $strategyName): MailerStrategyInterface
    {
        return match ($strategyName) {
            self::ITERATE   => new IterateMailer($this->mailer),
            self::ALL_CC    => new AllCcMailer($this->mailer),
            self::LOG_ONLY  => new LogOnlyMailer($this->mailer),
            default         => new AllBccMailer($this->mailer),
        };
    }
}
