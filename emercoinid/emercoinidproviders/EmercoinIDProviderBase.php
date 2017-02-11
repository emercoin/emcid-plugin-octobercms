<?php namespace Aspanta\EmercoinID\EmercoinIDProviders;

use Backend\Widgets\Form;
use Aspanta\EmercoinID\Models\Settings;

abstract class EmercoinIDProviderBase
{
	protected $settings;

	/**
	 * Initialize the singleton free from constructor parameters.
	 */
	protected function init()
	{
		$this->settings = Settings::instance();
	}

	/**
	 * Return true if the settings form has the 'enabled' box checked.
	 *
	 * @return boolean
	 */
	abstract public function isEnabled();

	/**
	 * Add any provider-specific settings to the settings form. Add a partial
	 * with a set of steps to follow to retrieve the credentials, an enabled
	 * checkbox and the settings fields like so:
	 *
	 * $form->addFields([
	 *		'noop' => [
	 *			'type' => 'partial',
	 *			'path' => '$/aspanta/emercoinid/partials/backend/forms/settings/_emercoinid_info.htm',
	 *			'tab' => 'Emercoin ID',
	 *		],
	 *
	 *		'providers[EmercoinID][enabled]' => [
	 *			'label' => 'Enabled?',
	 *			'type' => 'checkbox',
	 *			'default' => 'true',
	 *			'tab' => 'Emercoin ID',
	 *		],
	 *
	 *		'providers[EmercoinID][client_id]' => [
	 *			'label' => 'App Client ID',
	 *			'type' => 'text',
	 *			'tab' => 'Emercoin ID',
	 *		],
	 *
	 *		...
	 *	], 'primary');
	 *
	 * @param  Form   $form
	 *
	 * @return void
	 */
	abstract public function extendSettingsForm(Form $form);


	abstract public function login($provider_name, $action);
}