<?php namespace Tatter\WordPress\Database;

use CodeIgniter\Files\Exceptions\FileNotFoundException;
use CodeIgniter\Files\File;

/**
 * Class to extract database values from wp-config.php
 */
class ConfigReader
{
	/**
	 * Translation of WP to CI config
	 *
	 * @var array<string, string>
	 */
	protected $parseKeys = [
		'DB_NAME'      => 'database',
		'DB_USER'      => 'username',
		'DB_PASSWORD'  => 'password',
		'DB_HOST'      => 'hostname',
		'DB_CHARSET'   => 'charset',
		'DB_COLLATE'   => 'DBCollat',
		'table_prefix' => 'DBPrefix',
	];

	/**
	 * File instance for wp-config.php
	 *
	 * @var File
	 */
	protected $file;

	/**
	 * The extracted values
	 *
	 * @var array<string, mixed>
	 */
	protected $attributes = [];

	/**
	 * Verifies the config path, loads it into a File, and
	 * parses out the values.
	 *
	 * @param string $path
	 * @throws FileNotFoundException
	 */
	public function __construct(string $path)
	{
		$this->file = new File($path, true);

		$this->parse();
	}

	/**
	 * Returns translated keys compatible with app/Config/Database.php
	 *
	 * @return array<string, mixed>
	 */
	public function toParams(): array
	{
		$return = [];
		foreach ($this->parseKeys as $from => $to)
		{
			if (isset($this->attributes[$from]))
			{
				$return[$to] = $this->attributes[$from];
			}
		}

		return $return;
	}

	/**
	 * Parses database values from the file.
	 *
	 * @return $this
	 */
	protected function parse(): self
	{
		$lines = file($this->file->__toString());

		// Match lines like: define( 'DB_NAME', 'database_name_here' );
		$matched = preg_grep("/^define\(/", $lines);

		// Explode each line and extract values
		foreach ($matched as $line)
		{
			$array = explode("'", $line);
			if (count($array) === 5)
			{
				$this->attributes[$array[1]] = $array[3];
			}
		}

		// Grab the table prefix as well
		if ($matched = preg_grep("/^\$table_prefix/", $lines))
		{
			$array = explode("'", $lines[0]);
			if (count($array) === 3)
			{
				$this->attributes['table_prefix'] = $array[1];
			}
		}

		// If no table prefix was detected then use the default
		if (! isset($this->attributes['table_prefix']))
		{
			$this->attributes['table_prefix'] = 'wp_';
		}

		return $this;
	}

	//--------------------------------------------------------------------

	/**
	 * Magic method to allow retrieval of attributes.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get(string $key)
	{
		if (array_key_exists($key, $this->attributes))
		{
			return $this->attributes[$key];
		}

		return null;
	}

	//--------------------------------------------------------------------

	/**
	 * Magic method to all setting properties.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return $this
	 */
	public function __set(string $key, $value = null): self
	{
		$this->attributes[$key] = $value;

		return $this;
	}

	/**
	 * Unsets an attribute property.
	 *
	 * @param string $key
	 */
	public function __unset(string $key)
	{
		unset($this->attributes[$key]);
	}

	/**
	 * Returns true if the $key attribute exists.
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function __isset(string $key): bool
	{
		return isset($this->attributes[$key]);
	}
}