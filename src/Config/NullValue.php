<?php

namespace Joby\ContextInjection\Config;

class NullValue implements ConfigValue
{
    public function value(Config $config): null
    {
        return null;
    }
}