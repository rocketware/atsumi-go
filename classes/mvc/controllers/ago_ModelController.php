<?php
class ago_ModelController extends mvc_AbstractController {
	private $projectDir = '';
	private $configPath = '/classes/app/';
	private $allTables = false;
	private $namespace = '';
	function methodlessRequest() {
		$allArgs = func_get_args();

		if(!in_array('-p', $allArgs) || !in_array('-c', $allArgs)) {
			throw new app_InvalidUsageException();
		}

		$tables = array();
		if(!in_array('-a', $allArgs)) {
			$tables[] = array_pop($allArgs);
		}

		while($arg = array_shift($allArgs)) {
			switch($arg) {
				case '-p':
					$this->projectDir = array_shift($allArgs);
					break;
				case '-c':
					$this->configPath = array_shift($allArgs);
				case '-a': 
					$this->allTables = true;
					break;
				case '-n':
					$this->namespace = array_shift($allArgs);
					break;
				default:
					throw new app_InvalidUsageException();
			}
		}

		// Try and include the project config
		$settingsFile = sf('%s/%s', $this->projectDir, $this->configPath);
		if(!file_exists($settingsFile)) {
			throw new app_InvalidUsageException();
		}

		try {
			// Assumes settings file is a direct child of atsumi_AbstractSettings
			require $settingsFile;
		} catch(Exception $e) {
			throw new app_InvalidUsageException();
		}

		// Assumes standard Atsumi naming convention
		$matches = array();
		preg_match('/([^\/]+)\.php/i', $settingsFile, $matches);

		if(!isset($matches[1])) {
			throw new Exception('Could not parse class name');
		}

		$projectSettings = new $matches[1];

		$db = $projectSettings->init_db;
		if($this->allTables) {
			$tables = $db->showTables();
		}

		foreach($tables as $table) {
			$columnDefinition = $db->descTable($table);
			if(empty($columnDefinition)) {
				throw new app_InvalidUsageException('Dodgy table definition');
			}

			// Check if the file already exists
			$baseFile = sf('%s/classes/mvc/models/%sBaseModel.php', $this->projectDir, (($this->namespace == '') ? '' : $this->namespace));
			$modelFile = sf('%s/classes/mvc/models/%s%sModel.php', $this->projectDir, (($this->namespace == '') ? '' : $this->namespace . '_'), $table);
;
			if(file_exists($modelFile)) {
				// Do clever stuff here
				pfl('TODO'); return;
			} else {
				// Check if the parent class has already been created
				if(!file_exists($baseFile)) {
					// Create the base file first
					ob_start();
					require_once(sf('%s/classes/templates/ago_BaseModel.tpl', $this->app->get_baseDir));
					$baseModelContent = ob_get_contents();
					ob_end_clean();
				}
				ob_start();
				require_once(sf('%s/classes/templates/ago_BasicModel.tpl', $this->app->get_baseDir));
				$modelContent = ob_get_contents();
				ob_end_clean();
				
//				file_put_contents($modelFile, $modelContent);
			}
		}
	}
}
?>
