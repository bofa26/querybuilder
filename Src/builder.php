<?php 


/**
 * 
 */
class Builder
{

	/**
	 * 
	 * 	@var array
	 * 
	 */
	public array $body = array();
	/**
	 * 
	 * 	@var array
	 * 
	 */
	public array $select = array();
	/**
	 * 
	 * 	@var array
	 * 
	 */
	public array $where = array();
	/**
	 * 
	 * 	@var array
	 * 
	 */
	public array $where_in = array();
	/**
	 * 
	 * 	@var string
	 * 
	 */
	public string $table = '';
	/**
	 * 
	 * 	@var string FOR|OR Clause
	 * 
	 */
	public string $condition = '';
	/**
	 * 
	 * 	@var string
	 * 
	 */
	public string $query_type = '';
	/**
	 * 
	 * 	@var array
	 * 
	 */
	public array $alias = array();	
	/**
	 * 
	 * 	@var array
	 * 
	 */
	public array $group_by = array();// is used with aggregate functions
	/**
	 * 
	 * 	@var ?string
	 * 
	 */
	public ?string $having = null;

	public function select($from = "", $columns=[])
	{
		if ($from === "") {
			throw new Exception("Error Processing Request", 1);
		}

		$this->query_type = "select";

		if (empty($columns)) {
			$this->select[] = '*';
		}

		foreach ($columns as $column) {
			if ($column == "") {
				continue;
			}
			$column = trim($column);
			$this->select[] = $column;
		}

		$this->addTable($from);
		return $this;
	}

	public function where($key="", $operator="", $value="", $condition = "AND")
	{
		$args = func_get_args();
		$this->condition = (count($args) === 2) ? $args[1] : $condition;

		if (is_string($key)) {
			if (! in_array($operator, ["<>","<",">","=","!="])) {
				throw new Exception("Error Processing Request", 1);
			}
			$this->where[] = [$key, $operator, $value];
		}

		if (is_array($key)) {
			foreach ($key as  $value) {
				$value = trim($value);
				if (preg_match_all('/(<|>|!|=)/', $value, $matches, PREG_OFFSET_CAPTURE) == true){
					$index = 0;
					$opt = "";
					if (isset($matches[0][1])) {
						$index = $matches[0][1][1];
						$opt = $matches[0][0][0] . $matches[0][1][0];
						$val = substr($value, $index + 1);
						$key = substr($value, 0, $index - 1);
					}else{
						$opt = $matches[0][0][0];
						$index = $matches[0][0][1];
						$val = substr($value, $index + 1);
						$key = substr($value, 0, $index);
					}
					$this->where[] = [$key, $opt, $val];
				}else{
					throw new Exception("Error Processing Request", 1);
				}
			}
		}
		return $this;
	}

	public function addTable($table='')
	{
		$this->table = $table;
		return $this;
	}

	public function groupby(array $groups, $having = null)
	{
		if (empty($group)) {
			throw new Exception("Group By should be in list form");
		}


		foreach ($groups as $group) {
			$this->group_by[] = $group;
		}

		$this->having = $having;
	}

	public function where_in($where_in = [])
	{
		foreach($where_in as $where => $wherein){
			$this->where_in[$where] = $wherein;
		}
	}

	public function where_not_in($where_not_in = [])
	{
		$this->where_in($where_not_in);
	}

	public function compile_select()
	{
		$query = "SELECT ";
		$query .= implode(',', $this->select) . " FROM ".$this->getTable();

		if ($this->where) {
			$query .= " WHERE ";
			if (count($this->where) === 1) {
				$key = $this->where[0][0];
				$operator = $this->where[0][1];
				$val = $this->where[0][2];
				$this->body[$key] = $val;

				$query .= $key.$operator.':'.$key;
			}

			if (count($this->where) === 2) {
				$key = $this->where[0][0];
				$operator = $this->where[0][1];
				$val = $this->where[0][2];
				$seckey = $this->where[1][0];
				$secoperator = $this->where[1][1];
				$secval = $this->where[1][2];
				$this->body[$key] = $val;
				$this->body[$seckey] = $secval;

				$query .= $key.$operator.':'.$key." ".$this->condition." ".$seckey.$secoperator.':'.$seckey;
			}
		}
		return $query;
	}

	public function get()
	{
		//$db  = new Database();
		if ($this->query_type === 'select') {
			$query = $this->compile_select();
			//$db->select($query, $this->body);
			echo $query;
		}
	}

	public function getTable()
	{
		return $this->table;
	}
}