<?php

namespace app\src\Domain\EmailCampaign;

use app\services\MailerStrategyFactory;
use app\src\Shared\Config\AppSettings;

/**
 * Promotional campaign service.
 *
 * Implements the CampaignServiceInterface to send promotional
 * emails to a list of recipients using the configured mailer
 * strategy and template.
 */
class Promo implements CampaignServiceInterface
{
    /**
     * Send promotional emails to the given recipients.
     *
     * - Retrieves configuration values (sender, strategy, etc.).
     * - Builds either HTML or plain-text email content.
     * - Uses the MailerStrategyFactory to apply the chosen strategy.
     * - Logs the result of the sending process.
     *
     * @param string[] $emails List of recipient email addresses
     * @return void
     */
    public function send(array $emails): void
    {
        $subject     = 'Дякуємо за вашу активність! 🎉';
        $from        = AppSettings::get('campaign.fromEmail');
        $htmlEnabled = (int)AppSettings::get('campaign.htmlEnabled');
        $strategy    = AppSettings::get('campaign.mailStrategy') ?? MailerStrategyFactory::ALL_BCC;

        $data = [
            'offers'      => [
                ['title' => 'Знижка на навушники', 'discount' => 15, 'url' => 'https://example.local/offers/headphones'],
                ['title' => 'Суперціна на рюкзаки', 'discount' => 20, 'url' => 'https://example.local/offers/backpacks'],
            ],
            'fallbackUrl' => 'https://example.local/offers',
            'windowHours' => (int)AppSettings::get('campaign.hoursWindow'),
            'minViews'    => (int)AppSettings::get('campaign.minViews'),
        ];

        // Compose email content (HTML or plain text)
        if ($htmlEnabled) {
            $mailer = \Yii::$app->mailer->compose(
                ['html' => 'campaign', 'text' => 'campaign_text'],
                ['data' => $data]
            );
        } else {
            $plain  = "Привіт!\n\nМи помітили вашу активність за останні {$data['windowHours']} годин. ";
            $plain .= "Перегляньте пропозиції: {$data['fallbackUrl']}\n\n— Ваша команда маркетингу";
            $mailer = \Yii::$app->mailer->compose()->setTextBody($plain);
        }

        // Apply chosen mailer strategy
        $strategyFactory = new MailerStrategyFactory($mailer);
        $mailerStrategy  = $strategyFactory->create($strategy);

        $sent = $mailerStrategy->send($from, $subject, $emails);

        // Log result
        \Yii::info("Promo email sent. Status=" . ($sent ? 'SENT' : 'FAILED'), __METHOD__);
    }
}
