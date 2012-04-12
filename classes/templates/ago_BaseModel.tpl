/* START AUTO */
class {namespace}BaseModel {

	public function __call($name, $args) {
		$action = substr($name, 0, 3);
		// Assume camel case
		$var = lcfirst(substr($name, 3));
		switch($action) {
			case 'get':
				if(!isset($this->$var)) {
					return false;
				} else {
					return $this->$var;
				}
				break;
			case 'set':
				if(!isset($this->$var)) {
					return false;
				} else {
					$this->$var = $args[0];
					return true;
				}
				break;
			case default:
				throw new Exception('Missing call actions');
		}
	}

	abstract public static function write($db, $object);
	abstract private static function loadFromSqlRow($row);
	

/* FINISH AUTO */
