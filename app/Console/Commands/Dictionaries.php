<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ATehnix\VkClient\Client as VkClient;
use ATehnix\VkClient\Requests\Request as VkRequest;
use ATehnix\VkClient\Exceptions as VkException;
use App\Vk\Token;
use App\Vk\Category;
use App\Vk\City;
use App\Vk\Country;

class Dictionaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dictionaries:update {--refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var App\Vk\Token
     */
    protected $token;

    /**
     * @var VK\Client\VKApiClient
     */
    protected $client;

    public const COUNTRY_ID = 1;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Token::expired()->delete();

        if (!$this->token = Token::active()->first()) {
            return 1;
        }
        $this->client = new VkClient(env('VKONTAKTE_API_VERSION'));
        $this->client->setDefaultToken($this->token->token);

        $this->updateCategories();
        if ($this->option('refresh')) {
            $this->updateCountries();
            $this->updateCities();
        }
    }

    public function updateCities(?int $countryId = null)
    {
        if ($countryId === null) {
            $countryId = static::COUNTRY_ID;
        }

        $resp = $this->sendRequest('database.getCities', ['country_id' => $countryId, 'need_all' => 0]);

        City::where('country_id', $countryId)->delete();

        foreach ($resp['items'] as $city) {
            $city['country_id'] = $countryId;
            $city['name'] = &$city['title'];
            City::create($city);
        }
    }

    protected function updateCountries()
    {
        $resp = $this->sendRequest('database.getCountries', ['need_all' => 1, 'count' => 1000]);

        Country::query()->delete();

        foreach ($resp['items'] as $country) {
            $country['name'] = &$country['title'];
            Country::create($country);
        }
    }

    protected function updateCategories()
    {
        $resp = $this->sendRequest('ads.getCategories');

        Category::truncate();

        $traversSubCats = function($cat) use (&$traversSubCats)
        {
            if (!empty($cat['subcategories'])) {
                foreach ($cat['subcategories'] as $subCat) {
                    $subCat['parent_id'] = $cat['id'];
                    Category::create($subCat);
                    $traversSubCats($subCat);
                }
            }
        };

        foreach ($resp as $version => $cats) {
            foreach ($cats as $cat) {
                Category::create($cat);
                $traversSubCats($cat);
            }
        }
    }

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
            return 1;
        }
    }
}
