<?php
/**
 * Created by PhpStorm.
 * User: aleksey
 * Date: 08.02.19
 * Time: 2:51
 */

namespace App\Helpers;

use App\Vk\Category;
use App\Vk\Country;
use App\Vk\City;
use App\Vk\AdComment;
use App\Vk\Interest;

class VkAdHelper
{
    protected const UNDEF = 'Не указано';

    protected const LIST_DELIMITER = ',';

    /**
     * @var array
     */
    protected $ad;

    protected $filterField = [
        'timestamp' => [
            'start_time', 'stop_time',
        ],
        'cost' => [
            'day_limit', 'all_limit',
        ],
        'zeroUndefined' => [
            'day_limit', 'all_limit', 'start_time', 'stop_time',
        ],
    ];

    public function __construct(array $ad)
    {
        $this->ad = $ad;
    }

    public function __call($methodName, $args)
    {
        $methodName = substr($methodName, 3); // cutting "get" prefix

        if (method_exists($this, $methodName)) {
            return call_user_func_array([$this, $methodName], $args);
        }

        $methodName = snake_case($methodName);
        $val = $this->ad[$methodName] ?? static::UNDEF;

        foreach ($this->filterField as $filter => $fields) {
            foreach ($fields as $fieldName) {
                if ($methodName == $fieldName) {
                    $t = $args;
                    array_unshift($t, $val);
                    $val = call_user_func_array([$this, 'filter' . ucfirst($filter)], $t);
                }
            }
        }
        return $val;
    }

    public function cities()
    {
        $cities = City::find(static::decodeListValues($this->ad['targeting']['cities']))->pluck('name')->toArray();
        $citiesNot = City::find(static::decodeListValues($this->ad['targeting']['cities_not']))->pluck('name')->toArray();
        $ret = '';
        if ($cities) {
            $ret = implode(', ', $cities);
        }
        if ($citiesNot) {
            $ret .= ' Исключая: ' . implode(', ', $citiesNot);
        }
        if ($this->ad['targeting']['country']) {
            if ($ret) {
                $ret .= '; ';
            }
            $ret .= Country::find($this->ad['targeting']['country'])->name;
        }
        return $ret;
    }

    public function targetGroups()
    {
        $ret = [];
        $map = [
            'sex' => [1 => 'Женщины', 2 => 'Мужчины'],
            'age_from' => 'от',
            'age_to' => 'до',
        ];
        foreach ($map as $fieldName => $vals) {
            if (!empty($this->ad['targeting'][$fieldName])) {
                $ret[] = is_array($vals) ? $vals[$this->ad['targeting'][$fieldName]] : $vals . ' ' . $this->ad['targeting'][$fieldName];
            }
        }
        return implode(' ', $ret);
    }

    public function targetInterests()
    {
        if (empty($this->ad['targeting']['interest_categories'])) {
            return '';
        }
        $ret = array_map(function($el) {
            return Interest::find($el)->name;
        }, static::decodeListValues($this->ad['targeting']['interest_categories']));
        return implode(', ', $ret);
    }

    public function category()
    {
        $ret = [];
        for ($i = 1; $i < 3; ++$i) {
            if (!empty($this->ad['category' . $i . '_id'])) {
                $category = Category::find($this->ad['category' . $i . '_id']);
                $ret[$i] = $category->name;
                if ($category->parent_id) {
                    $ret[$i] .= ' (' . Category::find($category->parent_id)->name . ')';
                }
            }
        }
        return implode(', ', $ret);
    }

    public function audienceCount()
    {
        return $this->ad['targeting']['count'];
    }

    public function link()
    {
        return env('VKONTAKTE_LOAD_LAYOUTS') ? $this->ad['layout']['link_url'] : '';
    }

    protected function costTitle()
    {
        if ($this->ad['cost_type'] == 1) {
            return 'Цена за переход';
        } else {
            return 'Цена за 1000 показов';
        }
    }

    protected function cost()
    {
        if ($this->ad['cost_type'] == 1) {
            $val = $this->ad['cpm'];
        } else {
            $val = $this->ad['cpc'];
        }
        return $this->filterCost($val / 100);
    }

    protected function filterCost($val)
    {
        if (0 != $val) {
            $val = number_format($val, 2, ',', '');
            return preg_replace('/,00$/', '', $val) . ' руб.';
        } else {
            return static::UNDEF;
        }
    }

    protected function status()
    {
        $map = ['Остановлено', 'Запущено', 'Удалено'];
        return $map[$this->ad['status']];
    }

    protected function filterTimestamp($val)
    {
        if ($val == 0) {
            return 0;
        }
        return date('d.m.Y H:i:s', $val);
    }

    protected function filterZeroUndefined($amount, ?string $suffix = null) : string
    {
        if ($amount == 0) {
            return static::UNDEF;
        } else {
            return $amount . ($suffix !== null ? $suffix : '');
        }
    }

    public static function getJsonSerialized(array $params) : string
    {
        // TODO: обрабатывать строки. json_encode() нельзя
        return '[' . implode(',', $params) . ']';
    }

    public static function decodeListValues(?string $str) : array
    {
        return explode(static::LIST_DELIMITER, $str);
    }

    public static function encodeListValues(array $str) : string
    {
        return implode(static::LIST_DELIMITER, $str);
    }
}
