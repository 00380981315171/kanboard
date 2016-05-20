<?php

namespace Kanboard\Console;

use Kanboard\Core\Plugin\Base as BasePlugin;
use Kanboard\Core\Plugin\Installer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginUpgradeCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('plugin:upgrade')
            ->setDescription('Update all installed plugins')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Installer::isConfigured()) {
            $output->writeln('<error>Kanboard is not configured to upgrade plugins itself</error>');
        }

        $installer = new Installer($this->container);
        $availablePlugins = $this->httpClient->getJson(PLUGIN_API_URL);

        foreach ($this->pluginLoader->getPlugins() as $installedPlugin) {
            $pluginDetails = $this->getPluginDetails($availablePlugins, $installedPlugin);

            if ($pluginDetails === null) {
                $output->writeln('<error>* Plugin not available in the directory: '.$installedPlugin->getPluginName().'</error>');
            } elseif ($pluginDetails['version'] > $installedPlugin->getPluginVersion()) {
                $output->writeln('<comment>* Updating plugin: '.$installedPlugin->getPluginName().'</comment>');
                $installer->update($pluginDetails['download']);
            } else {
                $output->writeln('<info>* Plugin up to date: '.$installedPlugin->getPluginName().'</info>');
            }
        }
    }

    protected function getPluginDetails(array $availablePlugins, BasePlugin $installedPlugin)
    {
        foreach ($availablePlugins as $availablePlugin) {
            if ($availablePlugin['title'] === $installedPlugin->getPluginName()) {
                return $availablePlugin;
            }
        }

        return null;
    }
}
