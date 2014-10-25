<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping;

class Index
{
	private $algoliaName;
	private $perEnvironment = true;
	private $autoIndex = true;
	private $identifierFieldNames;

	private static $settingsProps = [
		'algoliaName',
		'perEnvironment',
		'autoIndex'
	];

	public function getAlgoliaName()
	{
		return $this->algoliaName;
	}

	public function setAlgoliaNameFromClass($class)
	{
		$this->algoliaName = substr($class, strrpos($class, '\\') + 1);

		return $this;
	}

	public function updateSettingsFromArray(array $settings)
	{
		foreach (self::$settingsProps as $field) {
			if (array_key_exists($field, $settings)) {
				$this->$field = $settings[$field];
			}
		}

		return $this;
	}

	public function getAutoIndex()
	{
		return $this->autoIndex;
	}

	public function getPerEnvironment()
	{
		return $this->perEnvironment;
	}
}