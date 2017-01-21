<?php

namespace Mindlahus\Helper;

class EntityRepositoryHelper
{

    /**
     * @param $results
     * @param array $y
     * @param array $x
     * @param array $o
     * @return array
     */
    public static function getMatrix($results, array $y, array $x, array $o)
    {
        self::_matrixInit($x, $y, $o);

        $r = [
            'y' => [],
            'x' => [],
            'y_x' => [],
            'x_y' => []
        ];

        foreach ($results as $result) {
            $t = self::_matrixGetTotal($result, $o);

            $y_key = $result[$y['key']];
            $x_key = $result[$x['key']];

            self::_matrixHandleLabels($result, $r, $y, $y_key, $x, $x_key);

            self::_matrixIncrementTotal($r, $t, $y_key, $x_key);
        }

        if (!empty($y['keys'])) {
            $r['y'] = $y['keys'];
        }

        if (!empty($x['keys'])) {
            $r['x'] = $x['keys'];
        }

        if ((isset($o['ksort']) AND $o['ksort'] === true)
            OR (isset($o['ksort_y']) AND $o['ksort_y'] === true)
        ) {
            ksort($r['y']);
        }

        if ((isset($o['ksort']) AND $o['ksort'] === true)
            OR (isset($o['ksort_x']) AND $o['ksort_x'] === true)
        ) {
            ksort($r['x']);
        }

        self::_matrixSetFirstKey($r, $y, $x);

        self::_matrixAddTotal($r);

        return $r;
    }

    /**
     * @param $x
     * @param $y
     * @param $o
     * @throws \Throwable
     */
    public static function _matrixInit(&$x, &$y, $o)
    {
        if (!isset($y['key'])) {
            throw new \Error('Missing key with name key in array with name $y[]');
        }
        if (!isset($y['val'])) {
            $y['val'] = $y['key'];
        }
        if (!isset($x['key'])) {
            throw new \Error('Missing key with name key in array with name $x[]');
        }
        if (!isset($x['val'])) {
            $x['val'] = $x['key'];
        }
        if (!isset($o['total']) AND !isset($o['pointerToTotal'])) {
            throw new \Error('Missing one of the required keys total, pointerToTotal in array with name $o[]');
        }
    }

    /**
     * @param $result
     * @param $o
     * @return mixed
     */
    public static function _matrixGetTotal($result, $o)
    {
        return (isset($o['pointerToTotal']) ? $result[$result[$o['pointerToTotal']]] : $result[$o['total']]);
    }

    /**
     * @param $result
     * @param $r
     * @param $y
     * @param $y_key
     * @param $x
     * @param $x_key
     */
    public static function _matrixHandleLabels($result, &$r, $y, $y_key, $x, $x_key)
    {
        if (!isset($r['y'][$y_key])) {
            $r['y'][$y_key] = (isset($y['keys'][$y_key]) ? $y['keys'][$y_key] : $result[$y['val']]);
        }

        if (!isset($r['x'][$x_key])) {
            $r['x'][$x_key] = (isset($x['keys'][$x_key]) ? $x['keys'][$x_key] : $result[$x['val']]);
        }
    }

    /**
     * @param $r
     * @param $t
     * @param $y_key
     * @param $x_key
     */
    public static function _matrixIncrementTotal(&$r, $t, $y_key, $x_key)
    {
        $r['y_x'][$y_key][$x_key] = (!isset($r['y_x'][$y_key][$x_key])
            ? $t
            : $r['y_x'][$y_key][$x_key] + $t);
        $r['y_x'][$y_key]['Total'] = (!isset($r['y_x'][$y_key]['Total'])
            ? $t
            : $r['y_x'][$y_key]['Total'] + $t);
        $r['y_x']['Total'][$x_key] = (!isset($r['y_x']['Total'][$x_key])
            ? $t
            : $r['y_x']['Total'][$x_key] + $t);

        $r['x_y'][$x_key][$y_key] = (!isset($r['x_y'][$x_key][$y_key])
            ? $t
            : $r['x_y'][$x_key][$y_key] + $t);
        $r['x_y'][$x_key]['Total'] = (!isset($r['x_y'][$x_key]['Total'])
            ? $t
            : $r['x_y'][$x_key]['Total'] + $t);
        $r['x_y']['Total'][$y_key] = (!isset($r['x_y']['Total'][$y_key])
            ? $t
            : $r['x_y']['Total'][$y_key] + $t);
    }

    /**
     * @param $r
     */
    public static function _matrixAddTotal(&$r)
    {
        $r['y']['Total'] = 'Total';
        $r['x']['Total'] = 'Total';

        $r['y_x']['Total']['Total'] = (isset($r['y_x']['Total'])
            ? array_sum($r['y_x']['Total'])
            : 0);

        $r['x_y']['Total']['Total'] = (isset($r['x_y']['Total'])
            ? array_sum($r['x_y']['Total'])
            : 0);
    }

    /**
     * @param $r
     * @param $y
     * @param $x
     */
    public static function _matrixSetFirstKey(&$r, $y, $x)
    {
        if (isset($y['first_key']) AND isset($r['y'][$y['first_key']])) {
            $first = $r['y'][$y['first_key']];
            unset($r['y'][$y['first_key']]);
            $r['y'] = array_merge([$y['first_key'] => $first], $r['y']);
        }

        if (isset($x['first_key']) AND isset($r['x'][$x['first_key']])) {
            $first = $r['x'][$x['first_key']];
            unset($r['x'][$x['first_key']]);
            $r['x'] = array_merge([$x['first_key'] => $first], $r['x']);
        }
    }
}