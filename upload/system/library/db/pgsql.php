<?php
namespace DB;
class PgSQL {
	private $connection;

	public function __construct($hostname, $username, $password, $database, $port = '5432') {
		if (!$port) {
			$port = '5432';
		}

		try {
			$pg = @pg_connect('host=' . $hostname . ' port=' . $port .  ' user=' . $username . ' password='	. $password . ' dbname=' . $database . ' options=\'--client_encoding=UTF8\' ');
		} catch (\Exception $e) {
			throw new \Exception('Error: Could not make a database link using ' . $username . '@' . $hostname);
		}

		if ($pg) {
			$this->connection = $pg;
			
			pg_query($this->connection, "SET CLIENT_ENCODING TO 'UTF8'");
		}
	}

	public function query($sql) {
		$resource = pg_query($this->connection, $sql);

		if ($resource) {
			if (is_resource($resource)) {
				$i = 0;

				$data = array();

				while ($result = pg_fetch_assoc($resource)) {
					$data[$i] = $result;

					$i++;
				}

				pg_free_result($resource);

				$query = new \stdClass();
				$query->row = isset($data[0]) ? $data[0] : array();
				$query->rows = $data;
				$query->num_rows = $i;

				unset($data);

				return $query;
			} else {
				return true;
			}
		} else {
			throw new \Exception('Error: ' . pg_result_error($this->connection) . '<br/>' . $sql);
		}
	}

	public function escape($value) {
		return pg_escape_string($this->connection, $value);
	}

	public function countAffected() {
		return pg_affected_rows($this->connection);
	}
	
	public function isConnected() {
		if (pg_connection_status($this->connection) == PGSQL_CONNECTION_OK) {
			return true;
		} else {
			return false;
		}
	}

	public function getLastId() {
		$query = $this->query("SELECT LASTVAL() AS `id`");

		return $query->row['id'];
	}

	public function __destruct() {
		if ($this->connection) {
			pg_close($this->connection);

			$this->connection = '';
		}
	}
}