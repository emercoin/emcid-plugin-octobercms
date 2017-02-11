<?php namespace Aspanta\EmercoinID\Models;

use Model;

class Settings extends Model
{
	public $implement = ['System.Behaviors.SettingsModel'];

	// A unique code
	public $settingsCode = 'aspanta_emercoinid_settings';

	// Reference to field configuration
	public $settingsFields = 'fields.yaml';

	protected $cache = [];
}