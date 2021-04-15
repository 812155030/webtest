<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Setup\Model;

use InvalidArgumentException as InvalidArgumentException;
use Magento\Framework\Filesystem\Io\File as FileSystem;
use Magento\Framework\Component\ComponentRegistrar;

class ModuleDataProvider
{
    const MODULE_DATA_FOLDER = 'data';

    /**
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * @var ComponentRegistrar
     */
    private $moduleDirectoryProvider;

    public function __construct(
        FileSystem $fileSystem,
        ComponentRegistrar $moduleDirectoryProvider
    ) {
        $this->fileSystem = $fileSystem;
        $this->moduleDirectoryProvider = $moduleDirectoryProvider;
    }

    public function getModuleDataFilePath(string $fileName, string $directory = ''): string
    {
        if (!preg_match('/(^[a-z_]+$)|(^$)/m', $directory)) {
            throw new InvalidArgumentException(__('Invalid directory provided')->render());
        }

        $moduleDirectory = $this->moduleDirectoryProvider->getPath(
            ComponentRegistrar::MODULE,
            'Amasty_ElasticSearch'
        );
        $moduleDataDirectory = $moduleDirectory . DIRECTORY_SEPARATOR . self::MODULE_DATA_FOLDER;

        if ($directory !== '') {
            $moduleDataDirectory .= DIRECTORY_SEPARATOR . $directory;
        }

        $filePath = $moduleDataDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!$this->fileSystem->fileExists($filePath)) {
            throw new InvalidArgumentException(__('Module data file doesn\'t exists.')->render());
        }

        return $filePath;
    }

    public function getModuleDataFileContent(string $fileName, string $directory = ''): string
    {
        return $this->fileSystem->read(
            $this->getModuleDataFilePath($fileName, $directory)
        );
    }
}
