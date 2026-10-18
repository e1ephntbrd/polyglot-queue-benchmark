<?php

namespace app\src\Domain\EmailCampaign;

interface CampaignServiceInterface
{
    /**
     * Send campaign emails to a list of recipients.
     *
     * @param string[] $emails
     * @return void
     */
    public function send(array $emails): void;
}
