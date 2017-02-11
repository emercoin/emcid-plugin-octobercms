<?php

// http://home.aspanta.com/aspanta/emercoinid/EmercoinID?s=/&f=/login
Route::get('aspanta/emercoinid/{provider}/{action?}', array("as" => "aspanta_emercoinid_provider", function($provider_name, $action = "")
{
	$success_redirect = Input::get('s', '/');
	$error_redirect = Input::get('f', '/');

	$manager = Aspanta\EmercoinID\Classes\ProviderManager::instance();
	$provider_class = $manager->resolveProvider($provider_name);

	if ( !$provider_class )
		return Redirect::to($error_redirect)->withErrors("Unknown login provider: $provider_name.");

	$provider = $provider_class::instance();

	try {
		// This will contain [token => ..., email => ..., ...]
		$provider_response = $provider->login($provider_name, $action);

		if ( !is_array($provider_response) )
			return Redirect::to($error_redirect);

		if ( isset($provider_response['error']))
			throw new Exception($provider_response['error']);

	} catch (Exception $e) {
		// Log the error
		Log::error($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());

		return Redirect::to($error_redirect)->withErrors([$e->getMessage()]);
	}

	$provider_details = [
		'provider_id' => $provider_name,
		'provider_token' => $provider_response['token'],
	];
	$user_details = array_except($provider_response, 'token');

	// Grab the user associated with this provider. Creates or attach one if need be.
	$user = \Aspanta\EmercoinID\Classes\UserManager::instance()->find(
		$provider_details,
		$user_details
	);

	Auth::login($user);

	return Redirect::to($success_redirect);
}))->where(['provider' => '[A-Z][a-zA-Z ]+']);