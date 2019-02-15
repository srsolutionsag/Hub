<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita40e2b05edaade5ad898a9098f79323c
{
    public static $prefixLengthsPsr4 = array (
        's' => 
        array (
            'srag\\RemovePluginDataConfirm\\Hub\\' => 33,
            'srag\\LibrariesNamespaceChanger\\' => 31,
            'srag\\DIC\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'srag\\RemovePluginDataConfirm\\Hub\\' => 
        array (
            0 => __DIR__ . '/..' . '/srag/removeplugindataconfirm/src',
        ),
        'srag\\LibrariesNamespaceChanger\\' => 
        array (
            0 => __DIR__ . '/..' . '/srag/librariesnamespacechanger/src',
        ),
        'srag\\DIC\\' => 
        array (
            0 => __DIR__ . '/..' . '/srag/dic/src',
        ),
    );

    public static $classMap = array (
        'srag\\DIC\\Hub\\DICStatic' => __DIR__ . '/..' . '/srag/dic/src/DICStatic.php',
        'srag\\DIC\\Hub\\DICStaticInterface' => __DIR__ . '/..' . '/srag/dic/src/DICStaticInterface.php',
        'srag\\DIC\\Hub\\DICTrait' => __DIR__ . '/..' . '/srag/dic/src/DICTrait.php',
        'srag\\DIC\\Hub\\DIC\\AbstractDIC' => __DIR__ . '/..' . '/srag/dic/src/DIC/AbstractDIC.php',
        'srag\\DIC\\Hub\\DIC\\DICInterface' => __DIR__ . '/..' . '/srag/dic/src/DIC/DICInterface.php',
        'srag\\DIC\\Hub\\DIC\\Implementation\\ILIAS52DIC' => __DIR__ . '/..' . '/srag/dic/src/DIC/Implementation/ILIAS52DIC.php',
        'srag\\DIC\\Hub\\DIC\\Implementation\\ILIAS53DIC' => __DIR__ . '/..' . '/srag/dic/src/DIC/Implementation/ILIAS53DIC.php',
        'srag\\DIC\\Hub\\DIC\\Implementation\\ILIAS54DIC' => __DIR__ . '/..' . '/srag/dic/src/DIC/Implementation/ILIAS54DIC.php',
        'srag\\DIC\\Hub\\DIC\\Implementation\\LegacyDIC' => __DIR__ . '/..' . '/srag/dic/src/DIC/Implementation/LegacyDIC.php',
        'srag\\DIC\\Hub\\Exception\\DICException' => __DIR__ . '/..' . '/srag/dic/src/Exception/DICException.php',
        'srag\\DIC\\Hub\\Output\\Output' => __DIR__ . '/..' . '/srag/dic/src/Output/Output.php',
        'srag\\DIC\\Hub\\Output\\OutputInterface' => __DIR__ . '/..' . '/srag/dic/src/Output/OutputInterface.php',
        'srag\\DIC\\Hub\\PHPVersionChecker' => __DIR__ . '/..' . '/srag/dic/src/PHPVersionChecker.php',
        'srag\\DIC\\Hub\\Plugin\\Plugin' => __DIR__ . '/..' . '/srag/dic/src/Plugin/Plugin.php',
        'srag\\DIC\\Hub\\Plugin\\PluginInterface' => __DIR__ . '/..' . '/srag/dic/src/Plugin/PluginInterface.php',
        'srag\\DIC\\Hub\\Plugin\\Pluginable' => __DIR__ . '/..' . '/srag/dic/src/Plugin/Pluginable.php',
        'srag\\DIC\\Hub\\Version\\Version' => __DIR__ . '/..' . '/srag/dic/src/Version/Version.php',
        'srag\\DIC\\Hub\\Version\\VersionInterface' => __DIR__ . '/..' . '/srag/dic/src/Version/VersionInterface.php',
        'srag\\LibrariesNamespaceChanger\\LibrariesNamespaceChanger' => __DIR__ . '/..' . '/srag/librariesnamespacechanger/src/LibrariesNamespaceChanger.php',
        'srag\\RemovePluginDataConfirm\\Hub\\AbstractPluginUninstallTrait' => __DIR__ . '/..' . '/srag/removeplugindataconfirm/src/AbstractPluginUninstallTrait.php',
        'srag\\RemovePluginDataConfirm\\Hub\\AbstractRemovePluginDataConfirm' => __DIR__ . '/..' . '/srag/removeplugindataconfirm/src/AbstractRemovePluginDataConfirm.php',
        'srag\\RemovePluginDataConfirm\\Hub\\PluginUninstallTrait' => __DIR__ . '/..' . '/srag/removeplugindataconfirm/src/PluginUninstallTrait.php',
        'srag\\RemovePluginDataConfirm\\Hub\\RemovePluginDataConfirmException' => __DIR__ . '/..' . '/srag/removeplugindataconfirm/src/RemovePluginDataConfirmException.php',
        'srag\\RemovePluginDataConfirm\\Hub\\RepositoryObjectPluginUninstallTrait' => __DIR__ . '/..' . '/srag/removeplugindataconfirm/src/RepositoryObjectPluginUninstallTrait.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita40e2b05edaade5ad898a9098f79323c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita40e2b05edaade5ad898a9098f79323c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita40e2b05edaade5ad898a9098f79323c::$classMap;

        }, null, ClassLoader::class);
    }
}
