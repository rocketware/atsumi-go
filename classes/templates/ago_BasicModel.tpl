/* START AUTO */
class {namespace}{class}Model extends {namespace}BaseModel {
	{properties}
	
	public static function write($db, {namespace}{class}Model $object) {
		try {
			$db->insert_or_update_1(
				'{table}',
				{condition}
				{values}
			);
			return true;
		} catch(Exception $e) {
			return false;
		}
	}

	private static function loadFromSqlRow($row) {
		$object = new self;
		{populateObject}
		return $object;
	}
	
/* FINISH AUTO */
