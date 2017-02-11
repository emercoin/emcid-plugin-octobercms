<?php namespace Aspanta\EmercoinID;

use Event;
use System\Classes\PluginBase;
use RainLab\User\Models\User;
use Backend\Widgets\Form;
use Aspanta\EmercoinID\Classes\ProviderManager;

/**
 * EmercoinID Plugin Information File
 *
 * https://github.com/Emercoin/emcid-plugin-octobercms
 *
 */
class Plugin extends PluginBase
{
	public $require = ['RainLab.User'];

	/**
	 * Returns information about this plugin.
	 *
	 * @return array
	 */
	public function pluginDetails()
	{
		return [
			'name'        => 'Emercoin ID',
			'description' => 'Emercoin ID Authorization Plugin',
			'author'      => 'Aspanta Limited',
			'icon'        => 'icon-users'
		];
	}

	public function registerSettings()
	{
		return [
			'settings' => [
				'label'       => 'Emercoin ID',
				'description' => 'Manage Emercoin ID settings.',
				'icon'        => 'icon-users',
				'class'       => 'Aspanta\EmercoinID\Models\Settings',
				'order'       => 600
			]
		];
	}

	public function registerComponents()
	{
		return [
			'Aspanta\EmercoinID\Components\EmercoinID' => 'emercoinid',
		];
	}

	public function boot()
	{
		User::extend(function($model) {
			$model->hasMany['aspanta_emercoinid_providers'] = ['Aspanta\EmercoinID\Models\Provider'];
		});

		Event::listen('backend.form.extendFields', function(Form $form) {
			if (!$form->getController() instanceof \System\Controllers\Settings) return;
			if (!$form->model instanceof \Aspanta\EmercoinID\Models\Settings) return;

			foreach ( ProviderManager::instance()->listProviders() as $class => $details )
			{
				$classObj = $class::instance();
				$classObj->extendSettingsForm($form);
			}
		});

		Event::listen('backend.form.extendFields', function($widget) {
			if (!$widget->getController() instanceof \RainLab\User\Controllers\Users) return;
			if ($widget->getContext() != 'update') return;

			$widget->addFields([
				'aspanta_emercoinid_providers' => [
					'label'   => 'Social Providers',
					'type'    => 'Aspanta\EmercoinID\FormWidgets\LoginProviders',
				],
			], 'secondary');
		});
	}

	function register_aspanta_emercoinid_providers()
	{
		return [
			'\\Aspanta\\EmercoinID\\EmercoinIDProviders\\EmercoinID' => [
				'label' => 'EmercoinID',
				'alias' => 'EmercoinID',
				'description' => 'Log in with Emercoin ID'
			],
		];
	}
}
