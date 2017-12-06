<?php

namespace Algolia\SearchBundle\Settings;



interface SettingsManagerInterface
{
    public function backup($settingsDir, array $params);

    public function push($settingsDir, array $params);
}
