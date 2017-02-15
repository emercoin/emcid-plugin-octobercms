<?php namespace Aspanta\EmercoinID\Components;

use Session;
use URL;
use Cms\Classes\ComponentBase;
use Aspanta\EmercoinID\Models\Settings;
use Aspanta\EmercoinID\Classes\ProviderManager;
use Illuminate\Support\ViewErrorBag;

class EmercoinID extends ComponentBase
{

	public function componentDetails()
	{
		return [
			'name'        => 'Emercoin ID',
			'description' => 'Adds emercoinid_link($provider, $success_url, $error_url) method.'
		];
	}

	/**
	 * Executed when this component is bound to a page or layout.
	 */
	public function onRun()
	{
		$providers = ProviderManager::instance()->listProviders();

		// MarkupManager::instance()->registerFunctions([
		// 	function($provider, $success_redirect='/', $error_redirect='/login') {
		// 		$settings = Settings::instance()->getHauthProviderConfig();
		// 		$is_enabled = !empty($settings[$provider]);

		// 		if ( !$is_enabled )
		// 			return '#';

		// 		return ProviderManager::instance()->getBaseURL($provider) .
		// 			'?s=' . URL::to($success_redirect) .
		// 			'&f=' . URL::to($error_redirect);
		// 	}
		// ]);

		$emercoinid_links = [];
		foreach ( $providers as $provider_class => $provider_details )
			if ( $provider_class::instance()->isEnabled() )
				$emercoinid_links[$provider_details['alias']] = URL::route('aspanta_emercoinid_provider', [$provider_details['alias']]);

		$this->page['emercoinid_links'] = $emercoinid_links;

		$this->page['errors'] = Session::get('errors', new ViewErrorBag);
	}
}