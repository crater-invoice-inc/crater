<?php

namespace Crater\Helpers;

class NumberToWordsUkrainian
{
    /**
     * Convert a number to Ukrainian words
     *
     * @param float $amount The amount to convert
     * @return string The amount in Ukrainian words
     */
    public static function convert(float $amount): string
    {
        // Split into hryvnias (whole part) and kopiykas (decimal part)
        $hryvnias = (int) floor($amount);
        $kopiykas = (int) round(($amount - $hryvnias) * 100);

        // Convert each part to words (use feminine for currency)
        $hryvniasWords = self::numberToWords($hryvnias, true);
        $hryvniasWord = self::getCurrencyDeclension($hryvnias, 'hryvnia');
        
        // Kopiykas are always shown with 2 digits
        $kopiykasFormatted = str_pad($kopiykas, 2, '0', STR_PAD_LEFT);
        $kopiykasWord = self::getCurrencyDeclension($kopiykas, 'kopiyka');

        return $hryvniasWords . ' ' . $hryvniasWord . ' ' . $kopiykasFormatted . ' ' . $kopiykasWord;
    }

    /**
     * Convert a number (0-999999999) to Ukrainian words
     *
     * @param int $number
     * @param bool $feminineCurrency Use feminine form for the final ones (for feminine nouns like гривня)
     * @return string
     */
    private static function numberToWords(int $number, bool $feminineCurrency = false): string
    {
        if ($number === 0) {
            return 'нуль';
        }

        $result = [];

        // Millions
        $millions = (int) floor($number / 1000000);
        if ($millions > 0) {
            $result[] = self::convertHundreds($millions);
            $result[] = self::getThousandDeclension($millions, 'million');
        }

        // Thousands
        $thousands = (int) floor(($number % 1000000) / 1000);
        if ($thousands > 0) {
            $result[] = self::convertHundreds($thousands, true); // feminine for thousands
            $result[] = self::getThousandDeclension($thousands, 'thousand');
        }

        // Ones - use feminine if this is for currency and no higher orders
        $ones = $number % 1000;
        if ($ones > 0) {
            $result[] = self::convertHundreds($ones, $feminineCurrency);
        }

        return implode(' ', $result);
    }

    /**
     * Convert hundreds (0-999) to Ukrainian words
     *
     * @param int $number
     * @param bool $feminine Use feminine form (for thousands)
     * @return string
     */
    private static function convertHundreds(int $number, bool $feminine = false): string
    {
        if ($number === 0) {
            return '';
        }

        $result = [];

        // Hundreds
        $hundreds = (int) floor($number / 100);
        if ($hundreds > 0) {
            $hundredsWords = [
                1 => 'сто', 2 => 'двісті', 3 => 'триста', 4 => 'чотириста',
                5 => 'п\'ятсот', 6 => 'шістсот', 7 => 'сімсот', 8 => 'вісімсот', 9 => 'дев\'ятсот'
            ];
            $result[] = $hundredsWords[$hundreds];
        }

        // Tens and ones
        $remainder = $number % 100;
        
        if ($remainder >= 10 && $remainder <= 19) {
            // Special case: 10-19
            $teensWords = [
                10 => 'десять', 11 => 'одинадцять', 12 => 'дванадцять', 13 => 'тринадцять',
                14 => 'чотирнадцять', 15 => 'п\'ятнадцять', 16 => 'шістнадцять',
                17 => 'сімнадцять', 18 => 'вісімнадцять', 19 => 'дев\'ятнадцять'
            ];
            $result[] = $teensWords[$remainder];
        } else {
            // Tens
            $tens = (int) floor($remainder / 10);
            if ($tens > 0) {
                $tensWords = [
                    2 => 'двадцять', 3 => 'тридцять', 4 => 'сорок', 5 => 'п\'ятдесят',
                    6 => 'шістдесят', 7 => 'сімдесят', 8 => 'вісімдесят', 9 => 'дев\'яносто'
                ];
                $result[] = $tensWords[$tens];
            }

            // Ones (1-9)
            $ones = $remainder % 10;
            if ($ones > 0) {
                if ($feminine) {
                    // Feminine forms for thousands
                    $onesWords = [
                        1 => 'одна', 2 => 'дві', 3 => 'три', 4 => 'чотири',
                        5 => 'п\'ять', 6 => 'шість', 7 => 'сім', 8 => 'вісім', 9 => 'дев\'ять'
                    ];
                } else {
                    // Masculine forms for regular numbers
                    $onesWords = [
                        1 => 'один', 2 => 'два', 3 => 'три', 4 => 'чотири',
                        5 => 'п\'ять', 6 => 'шість', 7 => 'сім', 8 => 'вісім', 9 => 'дев\'ять'
                    ];
                }
                $result[] = $onesWords[$ones];
            }
        }

        return implode(' ', $result);
    }

    /**
     * Get the correct declension for thousands/millions
     *
     * @param int $number
     * @param string $type 'thousand' or 'million'
     * @return string
     */
    private static function getThousandDeclension(int $number, string $type): string
    {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($type === 'thousand') {
            // Тисяча, тисячі, тисяч
            if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
                return 'тисяч';
            }
            if ($lastDigit === 1) {
                return 'тисяча';
            }
            if ($lastDigit >= 2 && $lastDigit <= 4) {
                return 'тисячі';
            }
            return 'тисяч';
        } else { // million
            // Мільйон, мільйони, мільйонів
            if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
                return 'мільйонів';
            }
            if ($lastDigit === 1) {
                return 'мільйон';
            }
            if ($lastDigit >= 2 && $lastDigit <= 4) {
                return 'мільйони';
            }
            return 'мільйонів';
        }
    }

    /**
     * Get the correct declension for currency
     *
     * @param int $number
     * @param string $currency 'hryvnia' or 'kopiyka'
     * @return string
     */
    private static function getCurrencyDeclension(int $number, string $currency): string
    {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($currency === 'hryvnia') {
            // Гривня, гривні, гривень
            if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
                return 'гривень';
            }
            if ($lastDigit === 1) {
                return 'гривня';
            }
            if ($lastDigit >= 2 && $lastDigit <= 4) {
                return 'гривні';
            }
            return 'гривень';
        } else { // kopiyka
            // Копійка, копійки, копійок
            if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
                return 'копійок';
            }
            if ($lastDigit === 1) {
                return 'копійка';
            }
            if ($lastDigit >= 2 && $lastDigit <= 4) {
                return 'копійки';
            }
            return 'копійок';
        }
    }
}
