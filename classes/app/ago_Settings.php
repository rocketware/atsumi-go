<?php
class ago_Settings extends atsumi_AbstractAppSettings {
	/* we can setup base settings here, can be useful for version numbers etc */
	protected $settings = 	array (
					'baseDir'	=> '/www/atsumi-go/',
					'debug'		=> true,
					'cli'		=> true
				);
	/* 	At times you may want to utilise the construct, this could be to 
	 * 	analyse what domain a user is on and you could give them an 
	 * 	alternate specification for example */
	public function __construct () {
		atsumi_Debug::setActive(false);
	}
	
	/* the specification is how atsumi knows what URI's call what class & method */
	public function init_specification () {
		return array (	
			'install'	=> 'ago_InstallController',
			'model'		=> 'ago_ModelController'
		);
	}
}
?>
