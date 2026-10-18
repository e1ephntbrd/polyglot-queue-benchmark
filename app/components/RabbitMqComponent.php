<?php

namespace app\components;

use yii\base\Component;

class RabbitMqComponent extends Component
{
    public string $host;
    public int $port;
    public string $user;
    public string $pass;
}
