<?php
class ago_InstallController extends mvc_AbstractController {
	function methodlessRequest() {
		$allArgs = func_get_args();

		if(count($allArgs) < 2) {
			throw new app_InvalidUsageException();
		}
		
		$projectFolder = array_pop($allArgs);
		$namespace = array_pop($allArgs);
		if(preg_match('/^[^A-z0-9_\/]+$/', $projectFolder) || preg_match('/^[^A-z0-9]+/', $namespace)) {
			throw new app_InvalidUsageException();
		}
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

		// We should already have atsumi, otherwise we wouldn't be here.
		// So, get the project base git repo and stick it in the dir we chose
		$gitOutput = array();
		$gitReturn = null;
		pf("Getting latest version of the Atsumi Base Project...");
		exec(sprintf('git clone -q git@github.com:phoenixrises/atsumi-project-base.git %s', $projectFolder), $gitOutput, $gitReturn);

		if($gitReturn !== 0) {
			// Git failed
			throw new Exception("Git failed to pull the project base");
		}
		pfl("Done.\n");

		// Scape every file added
		pf("Finding project files....");
		$dirListing = $this->scandir_r($projectFolder);
		pfl("found %s files\n", count($dirListing));

		// Current default name space is boot
		foreach($dirListing as $file) {
			$fileContents = file_get_contents($file);
			$replacementCount = 0;
			$fileContents = str_replace('boot', $namespace, $fileContents, $replacementCount);
			file_put_contents($file, $fileContents);
		       	pfl('Replaced %d items in %s', $replacementCount, $file);
			pf('Moving file...');
			rename($file, str_replace('boot', $namespace, $file));
			pfl('Done.');	
		}
	}

	private function goConfigureForCLI() {
		echo 'cli';
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
