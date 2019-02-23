<?php

namespace App\Http\Controllers\Vk;

use Illuminate\Http\Request;
use ATehnix\VkClient\Requests\ExecuteRequest;
use App\Http\Controllers\Controller;
use App\Vk\Token;
use ATehnix\VkClient\Client as VkClient;
use ATehnix\VkClient\Requests\Request as VkRequest;
use ATehnix\VkClient\Exceptions as VkException;
use App\Vk\City;
use App\Vk\Interest;
use App\Helpers\VkAdHelper;
use App\Vk\AdComment;

class Ads extends Controller
{
    /**
     * @var ATehnix\VkClient\Client
     */
    protected $client;

    /**
     * @var App\Vk\Token
     */
    protected $token;

    public function __construct()
    {
        if (!$this->token = Token::active()->first()) {
            return;
        }
        $this->initClient($this->token->token);
    }

    /**
     * User accounts list
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $resp = $this->sendRequest('ads.getAccounts');
        return view('vk/ads/cabinets', ['token' => $this->token, 'data' => $resp]);
    }

    /**
     * List of all campaigns of an account
     *
     * @param $cabinetId
     * @param $cabinetName
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function cabinet($cabinetId, $cabinetName)
    {
        $resp = $this->sendRequest('ads.getCampaigns', ['account_id' => $cabinetId, 'include_deleted' => 0]);
        return view('vk/ads/cabinet', ['token' => $this->token,
            'data' => $resp,
            'cabinetId' => $cabinetId,
            'cabinetName' => $cabinetName,
        ]);
    }

    /**
     * List of advertisements of a campaign
     *
     * @param $cabinetId
     * @param $cabinetName
     * @param $campaignId
     * @param $campaignName
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function campaign($cabinetId, $cabinetName, $campaignId, $campaignName)
    {
        $reqParams = ['account_id' => $cabinetId, 'campaign_ids' => VkAdHelper::getJsonSerialized([$campaignId]), 'include_deleted' => 0];

        $requests = [
            new VkRequest('ads.getAds', $this->diversifyRequest($reqParams)),
            new VkRequest('ads.getAdsTargeting', $this->diversifyRequest($reqParams)),
        ];
        if (env('VKONTAKTE_LOAD_LAYOUTS')) {
            $requests[] = new VkRequest('ads.getAdsLayout', $this->diversifyRequest($reqParams));
        }
        $execute = ExecuteRequest::make($requests);
        $response = $this->sendRequest($execute);

        if (env('VKONTAKTE_LOAD_LAYOUTS')) {
            $response[2] = collect($response[2])->keyBy('id')->all();
        }

        $cities = [];
        $interests = [];
        $ads = [];
        foreach ($response[0] as &$ad) {
            $ads[] = $ad['id'];
            foreach ($response[1] as $targeting) {
                if ($ad['id'] == $targeting['id']) {
                    $ad['targeting'] = $targeting;
                    $ad['layout'] = env('VKONTAKTE_LOAD_LAYOUTS') ? $response[2][$ad['id']] : [];
                    foreach (['cities', 'cities_not'] as $key) {
                        foreach (VkAdHelper::decodeListValues($targeting[$key]) as $cityId) {
                            $cities[$cityId] = $targeting['country'];
                        }
                    }
                    $interests = array_merge($interests, VkAdHelper::decodeListValues($targeting['interest_categories']));
                }
            }
        }
        $interests = array_unique($interests);

        $this->loadInterests($interests);
        $this->loadCities($cities);

        $comments = AdComment::find($ads)->keyBy('id')->all();

        return view('vk/ads/campaign', [
            'token' => $this->token,
            'data' => $response[0],
            'cabinetId' => $cabinetId,
            'cabinetName' => $cabinetName,
            'campaignId' => $campaignId,
            'campaignName' => $campaignName,
            'comments' => $comments,
        ]);
    }

    /**
     * VK oath authorization
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function auth(Request $request)
    {
        $auth = $this->getAuthClient();

        if ($request->filled('code')) {
            $resp = $auth->getUserData($request->input('code'));

            if (empty($resp['access_token'])) {
                throw new Exception('Unable to get token');
            }
            $resp['token'] = $resp['access_token'];

            $this->initClient($resp['token']);

            $userData = $this->sendRequest('users.get', [
                'user_ids' => [$resp['user_id']],
                'fields' => ['photo']
            ]);
            $userData[0]['photo_url'] = $userData[0]['photo'];
            $resp = array_merge($resp, $userData[0]);

            Token::truncate();
            $this->token = Token::create($resp);

            return redirect()->route('cabinets');
        } else {
            return redirect()->away($auth->getUrl());
        }
    }

    /**
     * Login page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function login()
    {
        return view('/vk/ads/login');
    }

    public function logout()
    {
        Token::truncate();
        return redirect()->route('login');
    }

    /**
     * Delete advertisement
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        // TODO: check result of deletion
        $resp = $this->sendRequest('ads.deleteAds', [
            'account_id' => $request->input('cabinetId'),
            'ids' => VkAdHelper::getJsonSerialized([$request->input('adId')])
        ]);
        if ($adComment = AdComment::find($request->input('adId'))) {
            $adComment->delete();
        }
        return $resp;
    }

    /**
     * Update advertisement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        return AdComment::updateOrCreate(['id' => $request->input('adId')], ['comment' => $request->input('comment')]);
    }

    /**
     * Avoiding \ATehnix\VkClient\Exceptions\TooMuchSimilarVkException. TODO: caching!
     *
     * @param array $request
     * @return array
     */
    protected function diversifyRequest(array $request) : array
    {
        $diversity  = [
            [],
            ['offset' => 0],
            ['limit' => 2000],
            ['offset' => 0, 'limit' => 2000],
        ];
        $key = mt_rand(0, count($diversity) - 1);
        return array_merge($request, $diversity[$key]);
    }

    /**
     * @param string $token VK auth token
     * @return VkClient
     */
    protected function initClient(string $token) : \ATehnix\VkClient\Client
    {
        $this->client = new VkClient(env('VKONTAKTE_API_VERSION'));
        $this->client->setDefaultToken($token);
        return $this->client;
    }

    /**
     * @return \ATehnix\VkClient\Auth
     */
    protected function getAuthClient() : \ATehnix\VkClient\Auth
    {
        return new \ATehnix\VkClient\Auth(
            env('VKONTAKTE_CLIENT_ID'),
            env('VKONTAKTE_SECRET'),
            env('VKONTAKTE_REDIRECT_URI'),
            env('VKONTAKTE_SCOPE') | 65536
        );
    }

    /**
     * Load cities from VK database
     *
     * @param array $data [cityId => countryId]
     * @return bool
     */
    protected function loadCities(array $data) : bool
    {
        $dbData = City::find(array_keys($data))->keyBy('id')->all();

        if ($toLoad = array_diff_key($data, $dbData)) {
            $resp = $this->sendRequest('database.getCitiesById', ['city_ids' => VkAdHelper::encodeListValues(array_keys($toLoad))]);
            foreach ($resp as $city) {
                City::updateOrCreate(
                    ['id' => $city['id']],
                    [
                        'name' => $city['title'],
                        'country_id' => $toLoad[$city['id']]
                    ]
                );
            }
        }
        return true;
    }

    /**
     * Load interests from VK database
     *
     * @param array $ids interest ids
     * @return bool
     */
    protected function loadInterests(array $ids) : bool
    {
        $dbData = Interest::find($ids)->keyBy('id')->all();

        if ($toLoad = array_diff($ids, array_keys($dbData))) {
            $resp = $this->sendRequest('ads.getSuggestions', [
                'section' => 'interest_categories_v2',
                'ids' => VkAdHelper::encodeListValues($toLoad)
            ]);
            foreach ($resp as $v) {
                Interest::updateOrCreate(
                    ['id' => $v['id']],
                    ['name' => $v['name']]
                );
            }
        }
        return true;
    }

    /**
     * Send request to VK API
     *
     * @param VkRequest|string $request
     * @param array $options
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendRequest($request, array $options = [])
    {
        try {
            // TODO: cache results
            $options['lang'] = env('APP_LOCALE');
            if (!$request instanceof VkRequest) {
                $request = new VkRequest($request, $options);
            }
            return $this->client->send($request)['response'];
        } catch (VkException\AuthorizationFailedVkException $e) {
            return redirect()->route('login');
        }
    }
}
