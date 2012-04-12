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
					break;
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
		
		// Check if a database method exists
		if(!method_exists($projectSettings, 'init_db')) {
			pfl('Missing init_db function in settings');
			throw new app_InvalidUsageException();
		}

		$db = $projectSettings->init_db;
		if($this->allTables) {
			$tables = $db->showTables();
		}

		foreach($tables as $table) {
			pfl('Generating model for table %s', $table);
			$columnDefinition = $db->descTable($table);
			if(empty($columnDefinition)) {
				throw new app_InvalidUsageException('Dodgy table definition');
			}

			// Check if the file already exists
			$baseFile = sf('%s/classes/mvc/models/%sBaseModel.php', $this->projectDir, (($this->namespace == '') ? '' : $this->namespace));
			$modelFile = sf('%s/classes/mvc/models/%s%sModel.php', $this->projectDir, (($this->namespace == '') ? '' : $this->namespace), str_replace(' ', '', ucwords(str_replace('_', ' ', $table))));
;

			$className = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
			// Set base data for template
			pfl("\t-Setting namespace as %s", $this->namespace);
			pfl("\t-Setting class name as %s", $className);
			$data = array('namespace' => $this->namespace, 'table' => $table, 'class' => $className);

			// Check if the parent class has already been created, if it's there, assume it's fine
			if(!file_exists($baseFile)) {
				pfl("\t-Generating model base class");
				// Create the base file first
				$baseModelContents = file_get_contents(sf('%s/classes/templates/ago_BaseModel.tpl', $this->app->get_baseDir));
				$matches = null;
				$baseModelContents = preg_replace('/{([A-z0-9]*)?}/e', '$data["$1"]', $baseModelContents);
				// New file, add PHP tags
				$baseModelContents = sf("<?php\n%s\n}\n?>", $baseModelContents);
				file_put_contents($baseFile, $baseModelContents);
				pfl("\t\t-Added Base Model class at %s", $baseFile);
			}

			// Define the class properties
			$properties = array();
			$constraints = array();
			foreach($columnDefinition as $column) {
				$properties[] = sf("private $%s; // %s %s %s\n", lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $column['name'])))), strtoupper($column['type']), (($column['nullable']) ? 'NULL' : 'NOT NULL'), (($column['constraint']) ? $column['constraint'] : ''));
				if($column['constraint']) {
					$constraints[] = $column;
				}
			}

			pfl("\t-Setting model properties");
			// Set the properties string for template
			$data['properties'] = implode("\t", $properties);
			// Define the WHERE clause for the write function
			if(!count($constraints)) {
				pfl("\t\t-Can't find key in table %s. Write condition will need set manually", $table);
				$data['condition'] = '';
			} else {
				$conditionArray = array();
				$conditionValues = array();
				foreach($constraints as $constraint) {
					// Define the data replacement type 
					$valueType = sf('%%%s', $this->getSubstitutionCode($constraint['type']));
					$conditionArray[] = sf('%s = %s', $constraint['name'], $valueType);
					$conditionValues[] = sf('$this->get%s()', str_replace(' ', '', ucwords(str_replace('_', ' ', $constraint['name']))));
				}

				// Concat and format the string into a usable atsumi clause
				$conditionString = sf('\'%s\'', implode(' AND ', $conditionArray));
				array_unshift($conditionValues, $conditionString);
				$conditionString = implode(', ', $conditionValues);

				pfl("\t-Setting write condition");
				$data['condition'] = $conditionString . ',';
			}

			// Define the SET strings for the values to insert or update
			$valuesArray = array();
			foreach($columnDefinition as $column) {
				$valueType = sf('%%%s', $this->getSubstitutionCode($column['type']));
				// Use the allow NULL data substitution type
				if($column['nullable']) {
					$valueType = strtoupper($valueType);
				}
				// Addd the getter to use to get the columns data
				$valuesArray[] = sf("'%s = %s', \$this->get%s()", $column['name'], $valueType, str_replace(' ', '', ucwords(str_replace('_', ' ', $column['name']))));
			}
			// Set the values data for the model
			pfl("\t-Setting write values");
			$data['values'] = implode(", \n\t\t\t\t", $valuesArray);

			// Create the populate object variable
			$populateArray = array();
			foreach($columnDefinition as $column) {
				$valueType = sf('%s', $this->getSubstitutionCode($column['type']));
				$populateArray[] = sf("\$object->set%s(\$this->%s_%s);\n", str_replace(' ', '', ucwords(str_replace('_', ' ', $column['name']))), $valueType, $column['name']);
			}
			pfl("\t-Generating object population code");
			$data['populateObject'] = implode("\t\t", $populateArray);

			// Get the content of the base model
			$newModelContent = file_get_contents(sf('%s/classes/templates/ago_BasicModel.tpl', $this->app->get_baseDir));
			// Replace all variables with variables from the data array
			$newModelContent = preg_replace('/{([A-z0-9]*)?}/e', '$data["$1"]', $newModelContent);	

			if(file_exists($modelFile)) {
				pfl("\t-Updating model file");
				$originalModel = file_get_contents($modelFile);
				$newModelContent = preg_replace('#\/\* START AUTO \*\/(.*)?\/\* FINISH AUTO \*\/#s', $newModelContent, $originalModel);
			} else {
				pfl("\t-Writing new model file");
				// It's a new file so we need to surround with PHP tags
				$newModelContent = sf("<?php\n%s\n}\n?>", $newModelContent);
			}
			file_put_contents($modelFile, $newModelContent);
			pfl("\t-Model generation complete\n");
		}
	}

	private function getSubstitutionCode($type) {
		$valueType = 's';
		// Define the data replacement type 
		switch(strtoupper($type)) {
			case 'INTEGER':
				$valueType = 'i';
				break;
			case 'BOOLEAN':
				$valueType = 'b';
				break;
			default: 
				if(strpos(strtoupper($type), 'TIMESTAMP') !== false) {
					$valueType = 't';
				}
				break;
		}
		return $valueType;
	}
}
?>
