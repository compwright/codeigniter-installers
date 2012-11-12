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
				// Move the core library extension out of the package directory and remove it
				$this->moveCoreFiles($downloadPath);
			break;
			
			case 'codeigniter-library':
				// Move the library files out of the package directory and remove it
				$wildcard = "MY_*.php";
				$path = realpath($downloadPath).'/'.$wildcard;
				if (count(glob($path)) > 0)
				{
					$this->moveCoreFiles($downloadPath, $wildcard);
				}
			break;
			
			case 'codeigniter-module':
				// If the module has migrations, copy them into the application migrations directory
				$moduleMigrations = glob($downloadPath.'migrations/*.php');
				if (count($moduleMigrations) > 0)
				{
					$migrationPath = dirname(dirname($downloadPath)).'/migrations/';
					// Create the application migration directory if it doesn't exist
					if ( ! file_exists($migrationPath))
					{
						mkdir($migrationPath, 0777, TRUE);
					}
					
					// @HACK to work around the security check in CI config files
					if ( ! defined('BASEPATH'))
					{
						define('BASEPATH', 1);
					}

					// Determine what type of migration naming style to use
					// (see https://github.com/EllisLab/CodeIgniter/pull/1949)
					$configPath = dirname(dirname($downloadPath)).'/config/';
					@include($configPath.'migration.php');
					if (isset($config['migration_type']) && $config['migration_type'] === 'timestamp')
					{
						$number = (int) date('YmdHis');
					}
					else
					{
						// Get the latest migration number and increment
						$migrations = glob($migrationPath.'*.php');
						if (count($migrations) > 0)
						{
							sort($migrations);
							$migration = array_pop($migrations);
							$number = ((int) basename($migration)) + 1;
						}
						else
						{
							$number = 1;
						}
					}
					
					// Copy each migration into the application migration directory
					sort($moduleMigrations);
					foreach ($moduleMigrations as $migration)
					{
						// Re-number the migration
						$newMigration = $migrationPath .
						                preg_replace('/^(\d+)/', sprintf('%03d', $number), basename($migration));
						
						// Copy the migration file
						copy($migration, $newMigration);
						
						$number++;
					}
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
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			shell_exec("move /Y $dir/$wildcard $dst/");
		}
		else
		{
			shell_exec("mv -f $dir/$wildcard $dst/");
		}
		
		// If there are no PHP files left in the package dir, remove the directory
		if (count(glob("$dir/*.php")) === 0)
		{
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			{
				shell_exec("rd /S /Q $dir");
			}
			else
			{
				shell_exec("rm -Rf $dir");
			}
		}
	}
}
