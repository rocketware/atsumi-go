<?php
class ago_InstallController extends mvc_AbstractController {
	function methodlessRequest() {
		$allArgs = func_get_args();

		if(count($allArgs) < 2) {
			throw new app_InvalidUsageException();
		}
		
		$projectFolder = array_pop($allArgs);
		$namespace = array_pop($allArgs);

		if(preg_match('/^[^A-z0-9]+/', $projectFolder) || preg_match('/^[^A-z0-9]+/', $namespace)) {
			throw new app_InvalidUsageException();
		}

		
	}

	private function goConfigureForCLI() {

	}

	private function goConfigureForDb($dbType) {
		
	}
}
?>
