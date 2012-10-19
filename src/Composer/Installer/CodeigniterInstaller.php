<?php

namespace Composer\Installer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * CodeIgniter package installer for Composer
 *
 * @package codeigniter-installers
 * @author  Jonathon Hill <jhill9693@gmail.com>
 * @license MIT license
 * @link    https://github.com/compwright/codeigniter-installers
 */
class CodeigniterInstaller extends LibraryInstaller
{
	protected $package_install_paths = array(
		'codeigniter-library'	 	=> '{application}/libraries/{name}/',
		'codeigniter-core'			=> '{application}/core/{name}/',
		'codeigniter-third-party'	=> '{application}/third_party/{name}/',
		'codeigniter-module' 	 	=> '{application}/modules/{name}/',
		'codeigniter-spark'  	 	=> '{sparks}/{name}/',
	);

	/**
	 * {@inheritDoc}
	 */
	public function supports($packageType)
	{
		return array_key_exists($packageType, $this->package_install_paths);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getInstallPath(PackageInterface $package)
	{
		$type = $package->getType();
		
		if (!isset($this->package_install_paths[$type]))
		{
			throw new \InvalidArgumentException("Package type '$type' is not supported at this time.");
		}

		$prettyName = $package->getPrettyName();
		if (strpos($prettyName, '/') !== false) {
			list($vendor, $name) = explode('/', $prettyName);
		} else {
			$vendor = '';
			$name = $prettyName;
		}
		
		$extra = ($this->composer->getPackage())
		       ? $this->composer->getPackage()->getExtra()
		       : array();
		
		$appdir = (!empty($extra['codeigniter-application-dir']))
		        ? $extra['codeigniter-application-dir']
		        : 'application';
		
		$sparksdir = (!empty($extra['codeigniter-sparks-dir']))
		           ? $extra['codeigniter-sparks-dir']
		           : 'sparks';
		
		$vars = array(
			'{name}'        => $name,
			'{vendor}'      => $vendor,
			'{type}'        => $type,
			'{application}' => $appdir,
			'{sparks}'      => $sparksdir,
		);
		
		return str_replace(array_keys($vars), array_values($vars), $this->package_install_paths[$type]);
	}
	
	/**
	 * {@inheritDoc}
	 */
	protected function installCode(PackageInterface $package)
	{
		$downloadPath = $this->getInstallPath($package);
		$this->downloadManager->download($package, $downloadPath);
		$this->postInstallActions($package->getType(), $downloadPath);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function updateCode(PackageInterface $initial, PackageInterface $target)
	{
		$downloadPath = $this->getInstallPath($initial);
		$this->downloadManager->update($initial, $target, $downloadPath);
		$this->postInstallActions($target->getType(), $downloadPath);
	}
	
	/**
	 * Performs actions on the downloaded files after an installation or update
	 * 
	 * @var string $type
	 * @var string $downloadPath
	 */
	protected function postInstallActions($type, $downloadPath)
	{
		switch ($type)
		{
			case 'codeigniter-core':
				$this->moveCoreFiles($downloadPath);
			break;
			
			case 'codeigniter-library':
				$wildcard = "MY_*.php";
				$path = realpath($downloadPath).'/'.$wildcard;
				if (count(glob($path)) > 0)
				{
					$this->moveCoreFiles($downloadPath, $wildcard);
				}
			break;
		}
	}
	
	/**
	 * Move files out of the package directory up one level
	 *
	 * @var $downloadPath
	 * @var $wildcard = '*.php'
	 */
	protected function moveCoreFiles($downloadPath, $wildcard = '*.php')
	{
		$dir = realpath($downloadPath);
		$dst = dirname($dir);
		
		// Move the files up one level
		shell_exec("mv -f $dir/$wildcard $dst/");
		
		// If there are no PHP files left in the package dir, remove the directory
		if (count(glob("$dir/*.php")) === 0)
		{
			shell_exec("rm -Rf $dir");
		}
	}
}
