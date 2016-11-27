<?php

namespace Contributte\EventDispatcher\Events\UI;

use ArrayIterator;
use Contributte\EventDispatcher\Exceptions\Logical\InvalidStateException;
use IteratorAggregate;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class Changeset implements IteratorAggregate
{

	/** @var array */
	private $changeset = [];

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function add($key, $value)
	{
		$this->changeset[$key] = $value;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return array_key_exists($key, $this->changeset);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		if (!$this->has($key)) {
			throw new InvalidStateException(sprintf('Key %s does not exist in this changeset', $key));
		}

		return $this->changeset[$key];
	}

	/**
	 * @return array
	 */
	public function all()
	{
		return $this->changeset;
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->changeset);
	}

}
