<?php
class ago_InstallController extends mvc_AbstractController {
	private $projectFolder;
	private $namespace;
	function methodlessRequest() {
		$allArgs = func_get_args();

		if(count($allArgs) < 2) {
			throw new app_InvalidUsageException();
		}
		
		$this->projectFolder = array_pop($allArgs);
		$this->namespace = array_pop($allArgs);
		if(preg_match('/^[^A-z0-9_\/]+$/', $this->projectFolder) || preg_match('/^[^A-z0-9]+/', $this->namespace)) {
			throw new app_InvalidUsageException();
		}

		// Check if the desination already exists
		if(file_exists($this->projectFolder)) {
			throw new Exception('Directory already exists. Please remove the directory or choose a new one to install your site into.');
		}

		// We should already have atsumi, otherwise we wouldn't be here.
		// So, get the project base git repo and stick it in the dir we chose
		$gitOutput = array();
		$gitReturn = null;
		pf("Getting latest version of the Atsumi Base Project...");
		exec(sprintf('git clone -q git@github.com:phoenixrises/atsumi-project-base.git %s', $this->projectFolder), $gitOutput, $gitReturn);

		if($gitReturn !== 0) {
			// Git failed
			throw new Exception("Git failed to pull the project base");
		}
		pfl("Done.\n");

		// Scape every file added
		pf("Finding project files....");
		$dirListing = $this->scandir_r($this->projectFolder);
		pfl("found %s files\n", count($dirListing));

		// Current default name space is boot
		foreach($dirListing as $file) {
			$fileContents = file_get_contents($file);
			$replacementCount = 0;
			$fileContents = str_replace('boot', $this->namespace, $fileContents, $replacementCount);
			if(strpos($fileContents, 'projectFolder') !== false) {
				pfl('Updated projectFolder settings');
				$fileContents = str_replace('projectFolder', substr($this->projectFolder, strrpos($this->projectFolder, '/') + 1), $fileContents);
			}

			file_put_contents($file, $fileContents);
		       	pfl('Replaced %d items in %s', $replacementCount, $file);
			pf('Moving file...');
			rename($file, str_replace('boot', $this->namespace, $file));
			pfl('Done.');	
		}

		pfl('Project Installed.');

		// Customise
		pfl('Customising Atsumi project install...');
		while($arg = array_shift($allArgs)) {
			switch($arg) {
				case '-c':
					$this->goConfigureForCLI();
					break;
				case '-d':
					$type = array_shift($allArgs);
					$this->goConfigureForDb($type);
					break;
				default:
					throw new app_InvalidUsageException();
			}
		}

		pfl('Customisation complete.');
	}

	private function goConfigureForCLI() {
		if(!file_exists(sf('%s/classes/app/%s_Settings.php', $this->projectFolder, $this->namespace))) {
			throw new Exception('Cannot find settings file');
		}
		// Create a new settings file
		$configContent = <<<CONFIG
<?php
abstract class {$this->namespace}_GoSettings extends atsumi_AbstractAppSettings {
	public function __construct() {
		parent::__construct();
		\$this->settings['cli'] = true;
	}
}
?>
CONFIG;

		// Put the config in place
		$check = file_put_contents(sf('%s/classes/app/%s_GoSettings.php', $this->projectFolder, $this->namespace), $configContent);
		if(!$check) {
			throw new Exception('Couldn\'t create new Atsumi-go config file');
		}

		pf('Updating base settings file...');
		$settings = file_get_contents(sf('%s/classes/app/%s_Settings.php', $this->projectFolder, $this->namespace));
		$settings = str_replace('atsumi_AbstractAppSettings', sf('%s_GoSettings', $this->namespace), $settings);
		$settings = preg_replace('/[,]{0,1}\s+\'cli\'\s+=>\s+false/i', '', $settings);
		$check = file_put_contents(sf('%s/classes/app/%s_Settings.php', $this->projectFolder, $this->namespace), $settings);

		if(!$check) {
			throw new Exception('Could not update original config file');
		}

		pfl('Done.');
	}

	private function goConfigureForDb($dbType) {
		if(!in_array($dbType, array('postgresql', 'mysql'))) {
			throw new Exception('Unknown database type');
		}
		echo $dbType;
	}

	private function scandir_r($folder) {
		$listing = scandir($folder);
		$data = array();
		foreach($listing as $item) {
			if($item == '.' || $item == '..' || $item == '.git') {
				continue; 
			}
			if(is_dir(sprintf('%s/%s', $folder, $item))) {
				$downStream = $this->scandir_r(sprintf('%s/%s', $folder, $item));

				$data = array_merge($data, $downStream);
			} else if(is_file(sprintf('%s/%s', $folder, $item))) {
				$data[] = sprintf('%s/%s', $folder, $item);
			} else {
				// No idea wtf this is...
			}
		}
		return $data;
	}
}
?>
