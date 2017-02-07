<?php namespace Aspanta\EmercoinID\EmercoinIDProviders;

use Backend\Widgets\Form;
use Aspanta\EmercoinID\EmercoinIDProviders\EmercoinIDProviderBase;
use Aspanta\EmercoinID\Models\Settings;
use RainLab\User\Models\User;
use Exception;
use DB;
use Request;
use Redirect;
use Input;
use URL;
use Auth;

class EmercoinID extends EmercoinIDProviderBase
{
	use \October\Rain\Support\Traits\Singleton;

	/**
	 * Initialize the singleton free from constructor parameters.
	 */
	protected function init()
	{
		parent::init();
	}

	public function isEnabled()
	{
		$providers = $this->settings->get('providers', []);

		return !empty($providers['EmercoinID']['enabled']);
	}

	public function extendSettingsForm(Form $form)
	{
		$form->addFields([
			'noop' => [
				'type' => 'partial',
				'path' => '$/aspanta/emercoinid/partials/backend/forms/settings/_emercoinid_info.htm',
				'tab' => 'Emercoin ID',
			],

			'providers[EmercoinID][enabled]' => [
				'label' => 'Enabled?',
				'type' => 'checkbox',
				'default' => 'true',
				'tab' => 'Emercoin ID',
			],

			'providers[EmercoinID][auth_page]' => [
				'label' => 'Auth Page',
				'type' => 'text',
				'comment' => 'Emercoin ID Auth Page (example: https://id.emercoin.net/oauth/v2/auth)',
				'tab' => 'Emercoin ID',
			],

			'providers[EmercoinID][token_page]' => [
				'label' => 'Token Page',
				'type' => 'text',
				'comment' => 'Emercoin ID Token Page (example: https://id.emercoin.net/oauth/v2/token)',
				'tab' => 'Emercoin ID',
			],

			'providers[EmercoinID][infocard]' => [
				'label' => 'Infocard Page',
				'type' => 'text',
				'comment' => 'Emercoin ID Infocard Page (example: https://id.emercoin.net/infocard)',
				'tab' => 'Emercoin ID',
			],

			'providers[EmercoinID][app_id]' => [
				'label' => 'App Client ID',
				'type' => 'text',
				'comment' => "Paste your App's Client ID",
				'tab' => 'Emercoin ID',
			],

			'providers[EmercoinID][app_secret]' => [
				'label' => 'App Secret Key',
				'type' => 'text',
				'comment' => "Paste your App's Secret Key",
				'tab' => 'Emercoin ID',
			],
		], 'primary');
	}

    private function generateSuffix()
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $suffix     = '';

        for ($i = 0; $i < 5; $i++) {
            $suffix .= $characters[mt_rand(0, 35)];
        }

        return $suffix;
    }

	private function generateUniqueUsername($emc_name)
	{
		$base_name = $emc_name;
		$base_name = strtolower(trim($base_name));
		$base_name = preg_replace('/ {1,}/', '-', $base_name);

		$candidate = strlen($base_name) > 3 ? $base_name : 'emcid_' . $this->generateSuffix();
		$base_name = strlen($base_name) > 3 ? $base_name : 'emcid_';

		while (User::where( 'username', $candidate )->first()) {
			$candidate = $base_name . '-' . $this->generateSuffix();
		}

		return $candidate;
	}

	private function generateUniqueEmail($email, $user_name)
	{
	    if (User::where( 'email', $email )->first() || !$email) {
	        $candidate = strtolower($user_name);
	        $candidate = trim($candidate);
	        $candidate = preg_replace('/ {1,}/', '', $candidate);

	        while (User::where( 'email', "$candidate@emercoinid.local" )->first()) {
	            $suffix = $this->generateSuffix();
	            $candidate = "$user_name-$suffix";
	        }

	        return "$candidate@emercoinid.local";
	    } else {
	        return $email;
	    }
	}

	private function getUserByEmcId($emc_user_id)
	{
		$user = DB::table('aspanta_emercoinid_user_providers')
 			->where('provider_token', $emc_user_id)
 			->first();

 		if ($user) {
 			return User::where( 'id', $user->user_id )->first();
 		} else {
 			return false;
 		}
	}

	private function assignEmcIdToUser($user_id, $emc_user_id)
	{
		DB::table('aspanta_emercoinid_user_providers')
			->insert(
				[
					'user_id'        => $user_id,
					'provider_id'    => 'emercoinid',
					'provider_token' => $emc_user_id,
				]
			);

		return;
	}

	public function login($provider_name, $action)
	{
		$providers = $this->settings->get('providers', []);

		if ($action != "auth" && (!Input::has('error') || Input::has('code'))) {
		    $authQ = http_build_query(
		        [
		            'client_id'     => $providers['EmercoinID']['app_id'],
		            'redirect_uri'  => URL::route('aspanta_emercoinid_provider', ['EmercoinID', 'auth']),
		            'response_type' => 'code',
		        ]
		    );
		    $path = $providers['EmercoinID']['auth_page'].'?'.$authQ;
		    header("Location: $path");
		    exit;
		} elseif (Input::has('code') && !Input::has('error') ) {
		    $opts = [
		        'http' => [
		            'method' => 'POST',
		            'header' => join(
		                "\r\n",
		                [
		                    'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
		                    'Accept-Charset: utf-8;q=0.7,*;q=0.7',
		                ]
		            ),
		            'content' => http_build_query(
		                [
		                    'code' => $_REQUEST['code'],
		                    'client_id' => $providers['EmercoinID']['app_id'],
		                    'client_secret' => $providers['EmercoinID']['app_secret'],
		                    'grant_type' => 'authorization_code',
		                    'redirect_uri' => URL::route('aspanta_emercoinid_provider', ['EmercoinID', 'auth']),
		                ]
		            ),
		            'ignore_errors' => true,
		            'timeout' => 10,
		        ],
		        'ssl' => [
		            "verify_peer" => false,
		            "verify_peer_name" => false,
		        ],
		    ];

			$response = @file_get_contents($providers['EmercoinID']['token_page'], false, stream_context_create($opts));
			$response = json_decode($response, true);

		    if (!array_key_exists('error', $response)) {
		        $infocard_url = $providers['EmercoinID']['infocard'];
		        $infocard_url .= '/'.$response['access_token'];
		        $opts = [
		            'http' => [
		                'method' => 'GET',
		                'ignore_errors' => true,
		                'timeout' => 10,
		            ],
		            'ssl' => [
		                "verify_peer" => false,
		                "verify_peer_name" => false,
		            ],
		        ];
		        $info = @file_get_contents($infocard_url, false, stream_context_create($opts));
		        $info = json_decode($info, true);

	            $emc_user = [
	                'emc_user_id' => strtolower($info['SSL_CLIENT_M_SERIAL']),
	                'email'       => isset($info['infocard']['Email'])     ? $info['infocard']['Email']     : '',
	                'first_name'  => isset($info['infocard']['FirstName']) ? $info['infocard']['FirstName'] : '',
	                'last_name'   => isset($info['infocard']['LastName'])  ? $info['infocard']['LastName']  : '',
	            ];

	            if ( empty( $emc_user['emc_user_id'] ) ) {
	                return [ 'error' => 'Invalid User' ];
	            }

				if ( $user = $this->getUserByEmcId($emc_user['emc_user_id']) ) {
					// LOGIN
	                Auth::login($user, true);

					return [
						'token'	   => $emc_user['emc_user_id'],
						'email'    => $user['email'],
						'username' => $user['username'] ?: $user['email'],
						'name'     => $user['name'].' '.$user['surname'],
					];
	            } else {
	            	// REGISTER USER
				    $name  = $this->generateUniqueUsername("{$emc_user['first_name']} {$emc_user['last_name']}");
				    $email = $this->generateUniqueEmail($emc_user['email'], $name);

					$password = uniqid();
			        $user = Auth::register([
				        'name'     => $emc_user['first_name'],
			            'surname'  => $emc_user['last_name'],
				        'email'    => $email,
				        'username' => $name,
				        'password' => $password,
				        'password_confirmation' => $password,
			        ], true);

			        $this->assignEmcIdToUser($user['id'], $emc_user['emc_user_id']);

	                Auth::login($user, true);

					return [
						'token'	   => $emc_user['emc_user_id'],
						'email'    => $user['email'],
						'username' => $user['username'] ?: $user['email'],
						'name'     => $user['name'].' '.$user['surname'],
					];
	            }
		    } else {
		        return [ 'error' => $response['error'] ];
		    }
		} else {
			return [ 'error' => Input::get('error_description') ];
		}
	}
}
