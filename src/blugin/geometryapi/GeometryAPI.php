<?php

namespace blugin\geometryapi;

use pocketmine\plugin\PluginBase;
use blugin\geometryapi\command\PoolCommand;
use blugin\geometryapi\command\subcommands\ListSubCommand;
use blugin\geometryapi\listener\PlayerEventListener;
use blugin\geometryapi\lang\PluginLang;

class GeometryAPI extends PluginBase{

    /** @var GeometryAPI */
    private static $instance = null;

    /** @return GeometryAPI */
    public static function getInstance() : GeometryAPI{
        return self::$instance;
    }

    /** @var PoolCommand */
    private $command;

    /** @var PluginLang */
    private $language;

    /** @var string[] */
    private $geometryDatas = [];

    public function onLoad() : void{
        self::$instance = $this;
    }

    public function onEnable() : void{
        if (!file_exists($dataFolder = $this->getDataFolder())) {
            mkdir($dataFolder, 0777, true);
        }
        if (!file_exists($jsonFolder = "{$dataFolder}json/")) {
            mkdir($jsonFolder, 0777, true);
        }
        $this->language = new PluginLang($this);

        $this->geometryDatas = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($jsonFolder)) as $path => $fileInfo) {
            if (!is_dir($path) && strcasecmp(substr($path, -5), '.json') === 0) {
                $this->geometryDatas[substr($fileName = $fileInfo->getFileName(), 0, strlen($fileName) - 5)] = file_get_contents($path);
            }
        }

        if ($this->command == null) {
            $this->command = new PoolCommand($this, 'geometry');
            $this->command->createSubCommand(ListSubCommand::class);
        }
        $this->command->updateTranslation();
        $this->command->updateSudCommandTranslation();
        if ($this->command->isRegistered()) {
            $this->getServer()->getCommandMap()->unregister($this->command);
        }
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->command);

        $this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener(), $this);
    }

    public function onDisable() : void{
        if (!file_exists($dataFolder = $this->getDataFolder())) {
            mkdir($dataFolder, 0777, true);
        }
        if (!file_exists($jsonFolder = "{$dataFolder}json/")) {
            mkdir($jsonFolder, 0777, true);
        }

        foreach ($this->geometryDatas as $geometryName => $geometryData) {
            file_put_contents("{$jsonFolder}{$geometryName}.json", $geometryData);
        }
    }

    /**  @return string[] */
    public function getGeometryDatas() : array{
        return $this->geometryDatas;
    }

    /**
     * @param string $geometryName
     * @param string $geometryData
     */
    public function addGeometryData(string $geometryName, string $geometryData) : void{
        if (!isset($this->geometryDatas[$geometryName])) {
            $this->geometryDatas[$geometryName] = $geometryData;
        }
    }

    /**
     * @param string $geometryName
     *
     * @return string | null
     */
    public function getGeometryData(string $geometryName) : ?string{
        return $this->geometryDatas[$geometryName] ?? null;
    }

    /**
     * @param string $name = ''
     *
     * @return PoolCommand
     */
    public function getCommand(string $name = '') : PoolCommand{
        return $this->command;
    }

    /**
     * @return PluginLang
     */
    public function getLanguage() : PluginLang{
        return $this->language;
    }

    /**
     * @return string
     */
    public function getSourceFolder() : string{
        $pharPath = \Phar::running();
        if (empty($pharPath)) {
            return dirname(__FILE__, 4) . DIRECTORY_SEPARATOR;
        } else {
            return $pharPath . DIRECTORY_SEPARATOR;
        }
    }
}
