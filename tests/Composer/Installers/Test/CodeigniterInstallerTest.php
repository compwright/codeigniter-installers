<?php
namespace Composer\Installers\Test;

use Composer\Installer\CodeigniterInstaller;
use Composer\Util\Filesystem;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Composer;
use Composer\Config;

class CodeigniterInstallerTest extends TestCase
{
    private $composer;
    private $config;
    private $vendorDir;
    private $binDir;
    private $dm;
    private $repository;
    private $io;
    private $fs;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        $this->fs = new Filesystem;

        $this->composer = new Composer();
        $this->config = new Config();
        $this->composer->setConfig($this->config);

        $this->vendorDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'baton-test-vendor';
        $this->ensureDirectoryExistsAndClear($this->vendorDir);

        $this->binDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'baton-test-bin';
        $this->ensureDirectoryExistsAndClear($this->binDir);

        $this->config->merge(array(
            'config' => array(
                'vendor-dir' => $this->vendorDir,
                'bin-dir' => $this->binDir,
            ),
        ));

        $this->dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->composer->setDownloadManager($this->dm);

        $this->repository = $this->getMock('Composer\Repository\InstalledRepositoryInterface');
        $this->io = $this->getMock('Composer\IO\IOInterface');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        $this->fs->removeDirectory($this->vendorDir);
        $this->fs->removeDirectory($this->binDir);
    }

    /**
     * testSupports
     *
     * @return void
     *
     * @dataProvider dataForTestSupport
     */
    public function testSupports($type, $expected)
    {
        $installer = new CodeigniterInstaller($this->io, $this->composer);
        $this->assertSame($expected, $installer->supports($type), sprintf('Failed to show support for %s', $type));
    }

    /**
     * dataForTestSupport
     */
    public function dataForTestSupport()
    {
        return array(
            array('codeigniter', false),
            array('codeigniter-', false),
            array('codeigniter-library', true),
            array('codeigniter-core', true),
            array('codeigniter-third-party', true),
            array('codeigniter-module', true),
            array('codeigniter-spark', true),
        );
    }

    /**
     * testInstallPath
     *
     * @dataProvider dataForTestInstallPath
     */
    public function testInstallPath($type, $path, $name)
    {
        $installer = new CodeigniterInstaller($this->io, $this->composer);
        $package = new Package($name, '1.0.0', '1.0.0');

        $package->setType($type);
        $result = $installer->getInstallPath($package);
        $this->assertEquals($path, $result);
    }

    /**
     * dataForTestInstallPath
     */
    public function dataForTestInstallPath()
    {
        return array(
            array('codeigniter-library', 'application/libraries/my_package/', 'shama/my_package'),
            array('codeigniter-core', 'application/core/my_package/', 'shama/my_package'),
            array('codeigniter-third-party', 'application/third_party/my_package/', 'shama/my_package'),
            array('codeigniter-module', 'application/modules/my_package/', 'shama/my_package'),
            array('codeigniter-spark', 'sparks/my_package/', 'shama/my_package'),
        );
    }

}