<?php

namespace VoterAuth;

use \Firebase\JWT\JWT;

class OAuthRequestHandler extends \RequestHandler
{
    public static $jwtKey;

    public static $responseMode = 'json';

    public static function handleRequest()
    {
        switch (static::shiftPath()) {
            case 'token':
                return static::handleTokenRequest();
            default:
                return static::throwInvalidRequestError('Only /token route is currently supported');
        }
    }

    public static function handleTokenRequest()
    {
        if (!static::$jwtKey) {
            return static::respondBadRequest('Server does not have OAuthRequestHandler::$jwtKey configured');
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return static::respondBadRequest('Request must be POST');
        }

        if (empty($_POST['grant_type']) || $_POST['grant_type'] != 'voter') {
            return static::respondBadRequest('grant_type must be "voter"', 'unsupported_grant_type');
        }

        if (empty($_POST['date_of_birth']) || empty($_POST['house_number'])) {
            return static::respondBadRequest('date_of_birth and house_number required');
        }

        $Voter = Voter::getByWhere([
            'BirthDate' => $_POST['date_of_birth'],
            'HouseNumber' => $_POST['house_number']
        ]);

        if (!$Voter) {
            return static::respondBadClient('date_of_birth and house_number match no registered voter');
        }

        return static::respond('token', [
            'token_type' => 'Bearer',
            'access_token' => JWT::encode([
                'title' => $Voter->Title,
                'first_name' => $Voter->FirstName,
                'middle_name' => $Voter->MiddleName,
                'last_name' => $Voter->LastName,
                'suffix' => $Voter->Suffix,
                'party_code' => $Voter->PartyCode,
                'house_number' => $Voter->HouseNumber,
                'house_number_suffix' => $Voter->HouseNumberSuffix,
                'street_name' => $Voter->StreetName,
                'apartment_number' => $Voter->ApartmentNumber,
                'address_line2' => $Voter->AddressLine2,
                'city' => $Voter->City,
                'state' => $Voter->State,
                'zip' => $Voter->Zip
            ], static::$jwtKey)
        ]);
    }

    public static function respondBadClient($message = null, $code = 'invalid_client')
    {
        header('HTTP/1.0 401 Unauthorized');
        return static::respondError($code, $message);
    }

    public static function respondBadRequest($message = null, $code = 'invalid_request')
    {
        header('HTTP/1.0 400 Bad Request');
        return static::respondError($code, $message);
    }

    public static function respondError($code, $message = null)
    {
        $responseData = [
            'error' => $code
        ];

        if ($message) {
            $responseData['error_description'] = $message;
        }

        return static::respond('error', $responseData);
    }
}