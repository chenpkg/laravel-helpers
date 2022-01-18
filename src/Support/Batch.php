<?php

/**
 * Created by Cestbon.
 * Author Cestbon <734245503@qq.com>
 * Date 2021/12/14 16:20
 */

namespace LaravelHelpers\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Batch
{
    /**
     * $userInstance = new \App\Models\User;
     * $value = [
     *     [
     *         'id' => 1,
     *         'status' => 'active',
     *         'nickname' => 'Mohammad'
     *     ],
     *     [
     *         'id' => 5,
     *         'status' => 'deactive',
     *         'nickname' => 'Ghanbari'
     *     ],
     *     [
     *         'id' => 7,
     *         'balance' => ['+', 500]
     *     ]
     * ];
     * $index = 'id';
     * Batch::update($userInstance, $value, $index);
     *
     * @param Model $table
     * @param array $values
     * @param string $index
     * @param bool $raw
     * @return false|int
     */
    public static function update(Model $table, array $values, $index = 'id', bool $raw = false)
    {
        $final = [];
        $ids = [];

        if (!count($values)) {
            return false;
        }

        if (!$index) {
            $index = $table->getKeyName();
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        foreach ($values as $key => $val) {
            $ids[] = $val[$index];

            if ($table->usesTimestamps()) {
                $updatedAtColumn = $table->getUpdatedAtColumn();

                if (!isset($val[$updatedAtColumn])) {
                    $val[$updatedAtColumn] = Carbon::now()->format($table->getDateFormat());
                }
            }

            foreach (array_keys($val) as $field) {
                if ($field === $index) {
                    continue;
                }

                // If increment / decrement
                if (gettype($val[$field]) == 'array') {
                    // If array has two values
                    if (!array_key_exists(0, $val[$field]) || !array_key_exists(1, $val[$field])) {
                        throw new \ArgumentCountError('Increment/Decrement array needs to have 2 values, a math operator (+, -, *, /, %) and a number');
                    }
                    // Check first value
                    if (gettype($val[$field][0]) != 'string' || !in_array($val[$field][0], ['+', '-', '*', '/', '%'])) {
                        throw new \TypeError('First value in Increment/Decrement array needs to be a string and a math operator (+, -, *, /, %)');
                    }
                    // Check second value
                    if (!is_numeric($val[$field][1])) {
                        throw new \TypeError('Second value in Increment/Decrement array needs to be numeric');
                    }
                    // Increment / decrement
                    $value = '`' . $field . '`' . $val[$field][0] . $val[$field][1];
                } else {
                    // Only update
                    $finalField = $raw ? static::mysqlEscape($val[$field]) : "'" . static::mysqlEscape($val[$field]) . "'";
                    $value = (is_null($val[$field]) ? 'NULL' : $finalField);
                }

                if (static::disableBacktick($driver)) {
                    $final[$field][] = 'WHEN ' . $index . ' = \'' . $val[$index] . '\' THEN ' . $value . ' ';
                } else {
                    $final[$field][] = 'WHEN `' . $index . '` = \'' . $val[$index] . '\' THEN ' . $value . ' ';
                }
            }
        }

        if (static::disableBacktick($driver)) {
            $cases = '';
            foreach ($final as $k => $v) {
                $cases .= '"' . $k . '" = (CASE ' . implode("\n", $v) . "\n"
                    . 'ELSE "' . $k . '" END), ';
            }

            $query = "UPDATE \"" . static::getFullTableName($table) . '" SET ' . substr($cases, 0, -2) . " WHERE \"$index\" IN('" . implode("','", $ids) . "');";
        } else {
            $cases = '';
            foreach ($final as $k => $v) {
                $cases .= '`' . $k . '` = (CASE ' . implode("\n", $v) . "\n"
                    . 'ELSE `' . $k . '` END), ';
            }

            $query = "UPDATE `" . static::getFullTableName($table) . "` SET " . substr($cases, 0, -2) . " WHERE `$index` IN(" . '"' . implode('","', $ids) . '"' . ");";
        }

        return DB::connection(static::getConnectionName($table))->update($query);
    }

    /**
     * 获取表名
     *
     * @param Model $table
     * @return string
     */
    private static function getFullTableName(Model $table)
    {
        return $table->getConnection()->getTablePrefix() . $table->getTable();
    }

    /**
     * Get connection name.
     *
     * @param Model $model
     * @return string|null
     * @author Ibrahim Sakr <ebrahimes@gmail.com>
     */
    private static function getConnectionName(Model $model)
    {
        if (!is_null($cn = $model->getConnectionName())) {
            return $cn;
        }

        return $model->getConnection()->getName();
    }

    /**
     * Escape values according to mysql.
     *
     * @param $fieldValue
     * @return array|string|string[]
     */
    public static function mysqlEscape($fieldValue)
    {
        if (is_array($fieldValue)) {
            return array_map(__METHOD__, $fieldValue);
        }

        if (is_bool($fieldValue)) {
            return (int)$fieldValue;
        }

        if (self::is_json($fieldValue)) {
            return self::safeJson($fieldValue);
        }

        if (!empty($fieldValue) && is_string($fieldValue)) {
            return str_replace(
                ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
                ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
                $fieldValue
            );
        }

        return $fieldValue;
    }

    /**
     * Disable Backtick.
     *
     * @param $drive
     * @return boolean
     */
    public static function disableBacktick($drive)
    {
        return in_array($drive, ['pgsql', 'sqlsrv']);
    }

    protected static function safeJsonString($fieldValue)
    {
        return str_replace(
            ["'"],
            ["''"],
            $fieldValue
        );
    }

    protected static function is_json($str): bool
    {
        if (!is_string($str)) {
            return false;
        }
        return json_decode($str, true) !== null;
    }

    protected static function safeJson($jsonData, $asArray = false)
    {
        $jsonData = json_decode($jsonData, true);
        $safeJsonData = [];
        if (!is_array($jsonData)) {
            return $jsonData;
        }
        foreach ($jsonData as $key => $value) {
            if (self::is_json($value)) {
                $safeJsonData[$key] = self::safeJson($value, true);
            } elseif (is_string($value)) {
                $safeJsonData[$key] = self::safeJsonString($value);
            } elseif (is_array($value)) {
                $safeJsonData[$key] = self::safeJson(json_encode($value), true);
            } else {
                $safeJsonData[$key] = $value;
            }
        }
        return $asArray ? $safeJsonData : json_encode($safeJsonData, JSON_UNESCAPED_UNICODE);
    }
}
