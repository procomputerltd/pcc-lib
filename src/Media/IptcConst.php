<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR
A PARTICULAR PURPOSE. See the GNU General Public License
for more details.
*/
/*
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : Constants and descriptions used by IPTC (International Press Telecommunications Council) image functions.
*/
namespace Procomputer\Pcclib\Media;

/**
 * Constants and descriptions used by IPTC (International Press Telecommunications Council) image functions.
 */
class IptcConst {

    const IPTC_OBJECT_NAME = 5;
    const IPTC_EDIT_STATUS = 7;
    const IPTC_PRIORITY = 10;
    const IPTC_CATEGORY = 15;
    const IPTC_SUPPLEMENTAL_CATEGORY = 20;
    const IPTC_FIXTURE_IDENTIFIER = 22;
    const IPTC_KEYWORDS = 25;
    const IPTC_RELEASE_DATE = 30;
    const IPTC_RELEASE_TIME = 35;
    const IPTC_SPECIAL_INSTRUCTIONS = 40;
    const IPTC_REFERENCE_SERVICE = 45;
    const IPTC_REFERENCE_DATE = 47;
    const IPTC_REFERENCE_NUMBER = 50;
    const IPTC_CREATED_DATE = 55;
    const IPTC_CREATED_TIME = 60;
    const IPTC_ORIGINATING_PROGRAM = 65;
    const IPTC_PROGRAM_VERSION = 70;
    const IPTC_OBJECT_CYCLE = 75;
    const IPTC_BYLINE = 80;
    const IPTC_BYLINE_TITLE = 85;
    const IPTC_CITY = 90;
    const IPTC_PROVINCE_STATE = 95;
    const IPTC_COUNTRY_CODE = 100;
    const IPTC_COUNTRY = 101;
    const IPTC_ORIGINAL_TRANSMISSION_REFERENCE = 103;
    const IPTC_HEADLINE = 105;
    const IPTC_CREDIT = 110;
    const IPTC_SOURCE = 115;
    const IPTC_COPYRIGHT_STRING = 116;
    const IPTC_CAPTION = 120;
    const IPTC_LOCAL_CAPTION = 121;

    public static function getDescriptions() {
        return array (
            self::IPTC_OBJECT_NAME                     => 'Object Name',
            self::IPTC_EDIT_STATUS                     => 'Edit Status',
            self::IPTC_PRIORITY                        => 'Priority',
            self::IPTC_CATEGORY                        => 'Category',
            self::IPTC_SUPPLEMENTAL_CATEGORY           => 'Supplemental Category',
            self::IPTC_FIXTURE_IDENTIFIER              => 'Fixture Identifier',
            self::IPTC_KEYWORDS                        => 'Keywords',
            self::IPTC_RELEASE_DATE                    => 'Release Date',
            self::IPTC_RELEASE_TIME                    => 'Release Time',
            self::IPTC_SPECIAL_INSTRUCTIONS            => 'Special Instructions',
            self::IPTC_REFERENCE_SERVICE               => 'Reference Service',
            self::IPTC_REFERENCE_DATE                  => 'Reference Date',
            self::IPTC_REFERENCE_NUMBER                => 'Reference Number',
            self::IPTC_CREATED_DATE                    => 'Created Date',
            self::IPTC_CREATED_TIME                    => 'Created Time',
            self::IPTC_ORIGINATING_PROGRAM             => 'Originating Program',
            self::IPTC_PROGRAM_VERSION                 => 'Program Version',
            self::IPTC_OBJECT_CYCLE                    => 'Object Cycle',
            self::IPTC_BYLINE                          => 'Byline',
            self::IPTC_BYLINE_TITLE                    => 'Byline Title',
            self::IPTC_CITY                            => 'City',
            self::IPTC_PROVINCE_STATE                  => 'Province State',
            self::IPTC_COUNTRY_CODE                    => 'Country Code',
            self::IPTC_COUNTRY                         => 'Country',
            self::IPTC_ORIGINAL_TRANSMISSION_REFERENCE => 'Original Transmission Reference',
            self::IPTC_HEADLINE                        => 'Headline',
            self::IPTC_CREDIT                          => 'Credit',
            self::IPTC_SOURCE                          => 'Source',
            self::IPTC_COPYRIGHT_STRING                => "Copyright",
            self::IPTC_CAPTION                         => "Caption",
            self::IPTC_LOCAL_CAPTION                   => 'Local Caption',
            );
    }
}