<?php

namespace VoterAuth;

use DB;
use Address;

class Voter extends \ActiveRecord
{
    public static $parties = [];

    public static $tableName = 'voters';
    public static $singularNoun = 'voter';
    public static $pluralNoun = 'voters';
    public static $updateOnDuplicateKey = true; // UPDATE on matching voter ID to deal with some duplicate IDs in file, looks like later row might have more data

    public static $fields = [
        'ID' => [
            'sourceColumn' => 1,
            'type' => 'string',
            'primary' => true
        ],
        'Title' => [
            'sourceColumn' => 2,
            'default' => null
        ],
        'FirstName' => [
            'sourceColumn' => 4,
            'default' => null
        ],
        'MiddleName' => [
            'sourceColumn' => 5,
            'default' => null
        ],
        'LastName' => [
            'sourceColumn' => 3,
            'default' => null
        ],
        'Suffix' => [
            'sourceColumn' => 6,
            'default' => null
        ],
        'Gender' => [
            'sourceColumn' => 7,
            'type' => 'enum',
            'default' => null,
            'values' => ['male', 'female'],
            'sourceConvert' => [__CLASS__, 'convertGender']
        ],
        'BirthDate' => [
            'sourceColumn' => 8,
            'default' => null,
            'type' => 'date',
            'sourceConvert' => [__CLASS__, 'convertDate']
        ],
        'RegistrationDate' => [
            'sourceColumn' => 9,
            'type' => 'date',
            'default' => null,
            'sourceConvert' => [__CLASS__, 'convertDate']
        ],
        'Status' => [
            'sourceColumn' => 10,
            'type' => 'enum',
            'default' => null,
            'values' => ['active', 'inactive'],
            'sourceConvert' => [__CLASS__, 'convertStatus']
        ],
        'StatusChangeDate' => [
            'sourceColumn' => 11,
            'type' => 'date',
            'default' => null,
            'sourceConvert' => [__CLASS__, 'convertDate']
        ],
        'PartyCode' => [
            'sourceColumn' => 12,
            'type' => 'enum',
            'default' => null,
            'values' => [] // populated dynamically from Voter::$parties by _initField override below
        ],
        'HouseNumber' => [
            'sourceColumn' => 13,
            'default' => null
        ],
        'HouseNumberSuffix' => [
            'sourceColumn' => 14,
            'default' => null
        ],
        'StreetName' => [
            'sourceColumn' => 15,
            'default' => null
        ],
        'ApartmentNumber' => [
            'sourceColumn' => 16,
            'default' => null
        ],
        'AddressLine2' => [
            'sourceColumn' => 17,
            'default' => null
        ],
        'City' => [
            'sourceColumn' => 18,
            'default' => null
        ],
        'State' => [
            'sourceColumn' => 19,
            'type' => 'enum',
            'default' => null,
            'values' => [] // populated dynamically from Address::$usStates by _initField override below
        ],
        'Zip' => [
            'sourceColumn' => 20,
            'default' => null
        ],
        'MailAddress1' => [
            'sourceColumn' => 21,
            'default' => null
        ],
        'MailAddress2' => [
            'sourceColumn' => 22,
            'default' => null
        ],
        'MailCity' => [
            'sourceColumn' => 23,
            'default' => null
        ],
        'MailState' => [
            'sourceColumn' => 24,
            'default' => null
        ],
        'MailZip' => [
            'sourceColumn' => 25,
            'default' => null
        ],
        'LastVoteDate' => [
            'sourceColumn' => 26,
            'type' => 'date',
            'default' => null,
            'sourceConvert' => [__CLASS__, 'convertDate']
        ],
        'PrecinctCode' => [
            'sourceColumn' => 27,
            'default' => null
        ],
        'PrecinctSplitID' => [
            'sourceColumn' => 28,
            'default' => null
        ],
        'LastChangedDate' => [
            'sourceColumn' => 29,
            'type' => 'date',
            'default' => null,
            'sourceConvert' => [__CLASS__, 'convertDate']
        ],
        'HomePhone' => [
            'sourceColumn' => 151,
            'default' => null
        ],
        'County' => [
            'sourceColumn' => 152,
            'default' => null
        ],
        'MailCounty' => [
            'sourceColumn' => 153,
            'default' => null
        ]
    ];

    protected static function _initField($field, $options = [])
    {
        if ($field == 'PartyCode') {
            $options['values'] = array_keys(static::$parties);
        }

        if ($field == 'State') {
            $options['values'] = array_keys(Address::$usStates);
        }

        return parent::_initField($field, $options);
    }

    public static function refreshFromFile($filePath)
    {
        if (!$fileHandle = fopen($filePath, 'r')) {
            throw new \Exception("Cannot open $filePath");
        }

        // wipe existing table
        try {
            DB::nonQuery('TRUNCATE TABLE `%s`', static::$tableName);
        } catch (\TableNotFoundException $e) {
            // if the table doesn't exist yet we don't need to truncate it
        }

        $fields = static::getClassFields();
        $count = 0;

        print("Starting import...\n");
        while ($row = fgetcsv($fileHandle, 9, "\t")) {
            $recordFields = [];

            // map columns to record based on sourceColumn field config
            foreach ($fields AS $fieldName => $fieldOptions) {
                if (empty($fieldOptions['sourceColumn'])) {
                    continue;
                }

                $value = $row[$fieldOptions['sourceColumn'] - 1] ?: null;

                if (!empty($fieldOptions['sourceConvert'])) {
                    $value = call_user_func($fieldOptions['sourceConvert'], $value);
                }

                $recordFields[$fieldName] = $value;
            }

            // save row
            static::create($recordFields, true);

            // report progress
            if ($count % 100 == 0) {
                printf("Imported %s rows\r", number_format($count));
            }

            $count++;
        }

        fclose($fileHandle);
        
        printf("\nFinished importing %u rows.\n", $count);
    }

    public static function convertGender($value) {
        switch ($value) {
            case 'M':
                return 'male';
            case 'F':
                return 'female';
            case 'U':
            default:
                return null;
        }
    }

    public static function convertStatus($value) {
        switch ($value) {
            case 'A':
                return 'active';
            case 'I':
                return 'inactive';
            default:
                return null;
        }
    }

    public static function convertDate($value) {
        if (!$value || !($value = trim($value))) {
            return null;
        }

        list ($month, $day, $year) = explode('/', $value);
        return "$year-$month-$day";
    }
}