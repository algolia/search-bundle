<?php

namespace Algolia\SearchBundle\Settings;

interface SettingsManagerInterface
{
    public function backup(array $params);

    public function push(array $params);
}
