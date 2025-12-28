<?php

namespace App\Helpers;

class ArabicNumberHelper
{
    /**
     * Arabic letter forms mapping
     * Each Arabic letter has 4 forms: isolated, initial, medial, final
     * Format: 'isolated' => ['isolated', 'final', 'initial', 'medial']
     */
    private static $arabicForms = [
        // Alef forms
        'ا' => ['ا', 'ﺎ', 'ا', 'ﺎ'],
        'أ' => ['أ', 'ﺄ', 'أ', 'ﺄ'],
        'إ' => ['إ', 'ﺈ', 'إ', 'ﺈ'],
        'آ' => ['آ', 'ﺂ', 'آ', 'ﺂ'],
        'ء' => ['ء', 'ء', 'ء', 'ء'],
        'ؤ' => ['ؤ', 'ﺆ', 'ؤ', 'ﺆ'],
        'ئ' => ['ئ', 'ﺊ', 'ﺋ', 'ﺌ'],
        // Ba forms
        'ب' => ['ب', 'ﺐ', 'ﺑ', 'ﺒ'],
        'ت' => ['ت', 'ﺖ', 'ﺗ', 'ﺘ'],
        'ث' => ['ث', 'ﺚ', 'ﺛ', 'ﺜ'],
        'ج' => ['ج', 'ﺞ', 'ﺟ', 'ﺠ'],
        'ح' => ['ح', 'ﺢ', 'ﺣ', 'ﺤ'],
        'خ' => ['خ', 'ﺦ', 'ﺧ', 'ﺨ'],
        'د' => ['د', 'ﺪ', 'د', 'ﺪ'],
        'ذ' => ['ذ', 'ﺬ', 'ذ', 'ﺬ'],
        'ر' => ['ر', 'ﺮ', 'ر', 'ﺮ'],
        'ز' => ['ز', 'ﺰ', 'ز', 'ﺰ'],
        'س' => ['س', 'ﺲ', 'ﺳ', 'ﺴ'],
        'ش' => ['ش', 'ﺶ', 'ﺷ', 'ﺸ'],
        'ص' => ['ص', 'ﺺ', 'ﺻ', 'ﺼ'],
        'ض' => ['ض', 'ﺾ', 'ﺿ', 'ﻀ'],
        'ط' => ['ط', 'ﻂ', 'ﻃ', 'ﻄ'],
        'ظ' => ['ظ', 'ﻆ', 'ﻇ', 'ﻈ'],
        'ع' => ['ع', 'ﻊ', 'ﻋ', 'ﻌ'],
        'غ' => ['غ', 'ﻎ', 'ﻏ', 'ﻐ'],
        'ف' => ['ف', 'ﻒ', 'ﻓ', 'ﻔ'],
        'ق' => ['ق', 'ﻖ', 'ﻗ', 'ﻘ'],
        'ك' => ['ك', 'ﻚ', 'ﻛ', 'ﻜ'],
        'ل' => ['ل', 'ﻞ', 'ﻟ', 'ﻠ'],
        'م' => ['م', 'ﻢ', 'ﻣ', 'ﻤ'],
        'ن' => ['ن', 'ﻦ', 'ﻧ', 'ﻨ'],
        'ه' => ['ه', 'ﻪ', 'ﻫ', 'ﻬ'],
        'و' => ['و', 'ﻮ', 'و', 'ﻮ'],
        'ي' => ['ي', 'ﻲ', 'ﻳ', 'ﻴ'],
        'ى' => ['ى', 'ﻰ', 'ﻯ', 'ﻰ'],
        'ة' => ['ة', 'ﺔ', 'ة', 'ﺔ'],
        'ﻻ' => ['ﻻ', 'ﻼ', 'ﻻ', 'ﻼ'],
    ];

    /**
     * Letters that don't connect to the next letter (only connect from right)
     */
    private static $nonConnectingLetters = ['ا', 'أ', 'إ', 'آ', 'د', 'ذ', 'ر', 'ز', 'و', 'ؤ', 'ة', 'ء'];

    /**
     * Check if a character is an Arabic letter
     */
    private static function isArabicLetter(string $char): bool
    {
        return isset(self::$arabicForms[$char]);
    }

    /**
     * Check if a letter connects to the next letter
     */
    private static function connectsToNext(string $char): bool
    {
        return self::isArabicLetter($char) && !in_array($char, self::$nonConnectingLetters);
    }

    /**
     * Shape Arabic text for proper display in DomPDF
     * Converts each letter to its appropriate positional form
     */
    public static function shapeArabicText(string $text): string
    {
        // Handle Lam-Alef ligatures first
        $text = str_replace(['لا', 'لأ', 'لإ', 'لآ'], ['ﻻ', 'ﻷ', 'ﻹ', 'ﻵ'], $text);

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        $len = count($chars);

        for ($i = 0; $i < $len; $i++) {
            $char = $chars[$i];

            if (!self::isArabicLetter($char)) {
                $result[] = $char;
                continue;
            }

            $prevChar = $i > 0 ? $chars[$i - 1] : null;
            $nextChar = $i < $len - 1 ? $chars[$i + 1] : null;

            $prevConnects = $prevChar && self::connectsToNext($prevChar);
            $nextIsArabic = $nextChar && self::isArabicLetter($nextChar);

            // Determine the form: 0=isolated, 1=final, 2=initial, 3=medial
            if ($prevConnects && $nextIsArabic && self::connectsToNext($char)) {
                // Medial form
                $form = 3;
            } elseif ($prevConnects && (!$nextIsArabic || !self::connectsToNext($char))) {
                // Final form
                $form = 1;
            } elseif (!$prevConnects && $nextIsArabic && self::connectsToNext($char)) {
                // Initial form
                $form = 2;
            } else {
                // Isolated form
                $form = 0;
            }

            $result[] = self::$arabicForms[$char][$form] ?? $char;
        }

        return implode('', $result);
    }

    /**
     * Reverse Arabic text for proper RTL display in DomPDF
     */
    public static function reverseArabicText(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $reversed = array_reverse($chars);
        return implode('', $reversed);
    }

    /**
     * Get Arabic words formatted for PDF (shaped and reversed for DomPDF RTL support)
     */
    public static function toArabicWordsForPdf(float $number, string $currency = 'EGP'): string
    {
        $text = self::toArabicWords($number, $currency);
        return self::prepareForPdf($text);
    }

    /**
     * Prepare any Arabic text for PDF display (shape and reverse)
     */
    public static function prepareForPdf(string $text): string
    {
        // First shape the text (apply proper letter forms)
        $shaped = self::shapeArabicText($text);
        // Then reverse for RTL display
        return self::reverseArabicText($shaped);
    }

    private static $ones = [
        0 => '',
        1 => 'واحد',
        2 => 'اثنان',
        3 => 'ثلاثة',
        4 => 'أربعة',
        5 => 'خمسة',
        6 => 'ستة',
        7 => 'سبعة',
        8 => 'ثمانية',
        9 => 'تسعة',
        10 => 'عشرة',
        11 => 'أحد عشر',
        12 => 'اثنا عشر',
        13 => 'ثلاثة عشر',
        14 => 'أربعة عشر',
        15 => 'خمسة عشر',
        16 => 'ستة عشر',
        17 => 'سبعة عشر',
        18 => 'ثمانية عشر',
        19 => 'تسعة عشر',
    ];

    private static $tens = [
        2 => 'عشرون',
        3 => 'ثلاثون',
        4 => 'أربعون',
        5 => 'خمسون',
        6 => 'ستون',
        7 => 'سبعون',
        8 => 'ثمانون',
        9 => 'تسعون',
    ];

    private static $hundreds = [
        1 => 'مائة',
        2 => 'مائتان',
        3 => 'ثلاثمائة',
        4 => 'أربعمائة',
        5 => 'خمسمائة',
        6 => 'ستمائة',
        7 => 'سبعمائة',
        8 => 'ثمانمائة',
        9 => 'تسعمائة',
    ];

    private static $thousands = [
        1 => 'ألف',
        2 => 'ألفان',
        3 => 'ثلاثة آلاف',
        4 => 'أربعة آلاف',
        5 => 'خمسة آلاف',
        6 => 'ستة آلاف',
        7 => 'سبعة آلاف',
        8 => 'ثمانية آلاف',
        9 => 'تسعة آلاف',
        10 => 'عشرة آلاف',
    ];

    private static $millions = [
        1 => 'مليون',
        2 => 'مليونان',
        3 => 'ثلاثة ملايين',
        4 => 'أربعة ملايين',
        5 => 'خمسة ملايين',
        6 => 'ستة ملايين',
        7 => 'سبعة ملايين',
        8 => 'ثمانية ملايين',
        9 => 'تسعة ملايين',
        10 => 'عشرة ملايين',
    ];

    /**
     * Convert a number to Arabic words with Egyptian Pound currency
     */
    public static function toArabicWords(float $number, string $currency = 'EGP'): string
    {
        if ($number == 0) {
            return 'صفر جنيه فقط لا غير';
        }

        $currencyName = self::getCurrencyName($currency);
        $subCurrencyName = self::getSubCurrencyName($currency);

        // Split into pounds and piasters
        $pounds = (int) floor($number);
        $piasters = (int) round(($number - $pounds) * 100);

        $result = '';

        if ($pounds > 0) {
            $result = self::convertNumber($pounds) . ' ' . self::getPoundWord($pounds, $currencyName);
        }

        if ($piasters > 0) {
            if ($pounds > 0) {
                $result .= ' و';
            }
            $result .= self::convertNumber($piasters) . ' ' . self::getPiasterWord($piasters, $subCurrencyName);
        }

        return $result . ' فقط لا غير';
    }

    /**
     * Convert a number (integer) to Arabic words
     */
    private static function convertNumber(int $number): string
    {
        if ($number == 0) {
            return 'صفر';
        }

        if ($number < 0) {
            return 'سالب ' . self::convertNumber(abs($number));
        }

        $result = '';

        // Millions
        if ($number >= 1000000) {
            $millions = (int) floor($number / 1000000);
            if ($millions <= 10 && isset(self::$millions[$millions])) {
                $result .= self::$millions[$millions];
            } else {
                $result .= self::convertNumber($millions) . ' مليون';
            }
            $number %= 1000000;
            if ($number > 0) {
                $result .= ' و';
            }
        }

        // Thousands
        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            if ($thousands <= 10 && isset(self::$thousands[$thousands])) {
                $result .= self::$thousands[$thousands];
            } elseif ($thousands < 100) {
                $result .= self::convertNumber($thousands) . ' ألف';
            } else {
                $result .= self::convertNumber($thousands) . ' ألف';
            }
            $number %= 1000;
            if ($number > 0) {
                $result .= ' و';
            }
        }

        // Hundreds
        if ($number >= 100) {
            $hundredsDigit = (int) floor($number / 100);
            $result .= self::$hundreds[$hundredsDigit];
            $number %= 100;
            if ($number > 0) {
                $result .= ' و';
            }
        }

        // Tens and ones
        if ($number > 0) {
            if ($number < 20) {
                $result .= self::$ones[$number];
            } else {
                $tensDigit = (int) floor($number / 10);
                $onesDigit = $number % 10;

                if ($onesDigit > 0) {
                    $result .= self::$ones[$onesDigit] . ' و' . self::$tens[$tensDigit];
                } else {
                    $result .= self::$tens[$tensDigit];
                }
            }
        }

        return $result;
    }

    /**
     * Get the appropriate pound word based on the number
     */
    private static function getPoundWord(int $number, array $currencyName): string
    {
        if ($number == 1) {
            return $currencyName['singular'];
        } elseif ($number == 2) {
            return $currencyName['dual'];
        } elseif ($number >= 3 && $number <= 10) {
            return $currencyName['plural_few'];
        } else {
            return $currencyName['plural'];
        }
    }

    /**
     * Get the appropriate piaster word based on the number
     */
    private static function getPiasterWord(int $number, array $subCurrencyName): string
    {
        if ($number == 1) {
            return $subCurrencyName['singular'];
        } elseif ($number == 2) {
            return $subCurrencyName['dual'];
        } elseif ($number >= 3 && $number <= 10) {
            return $subCurrencyName['plural_few'];
        } else {
            return $subCurrencyName['plural'];
        }
    }

    /**
     * Get currency name based on currency code
     */
    private static function getCurrencyName(string $currency): array
    {
        $currencies = [
            'EGP' => [
                'singular' => 'جنيه',
                'dual' => 'جنيهان',
                'plural_few' => 'جنيهات',
                'plural' => 'جنيه',
            ],
            'USD' => [
                'singular' => 'دولار',
                'dual' => 'دولاران',
                'plural_few' => 'دولارات',
                'plural' => 'دولار',
            ],
            'EUR' => [
                'singular' => 'يورو',
                'dual' => 'يورو',
                'plural_few' => 'يورو',
                'plural' => 'يورو',
            ],
            'SAR' => [
                'singular' => 'ريال',
                'dual' => 'ريالان',
                'plural_few' => 'ريالات',
                'plural' => 'ريال',
            ],
            'AED' => [
                'singular' => 'درهم',
                'dual' => 'درهمان',
                'plural_few' => 'دراهم',
                'plural' => 'درهم',
            ],
            'GBP' => [
                'singular' => 'جنيه إسترليني',
                'dual' => 'جنيهان إسترلينيان',
                'plural_few' => 'جنيهات إسترلينية',
                'plural' => 'جنيه إسترليني',
            ],
        ];

        return $currencies[$currency] ?? $currencies['EGP'];
    }

    /**
     * Get sub-currency name based on currency code
     */
    private static function getSubCurrencyName(string $currency): array
    {
        $subCurrencies = [
            'EGP' => [
                'singular' => 'قرش',
                'dual' => 'قرشان',
                'plural_few' => 'قروش',
                'plural' => 'قرش',
            ],
            'USD' => [
                'singular' => 'سنت',
                'dual' => 'سنتان',
                'plural_few' => 'سنتات',
                'plural' => 'سنت',
            ],
            'EUR' => [
                'singular' => 'سنت',
                'dual' => 'سنتان',
                'plural_few' => 'سنتات',
                'plural' => 'سنت',
            ],
            'SAR' => [
                'singular' => 'هللة',
                'dual' => 'هللتان',
                'plural_few' => 'هللات',
                'plural' => 'هللة',
            ],
            'AED' => [
                'singular' => 'فلس',
                'dual' => 'فلسان',
                'plural_few' => 'فلوس',
                'plural' => 'فلس',
            ],
            'GBP' => [
                'singular' => 'بنس',
                'dual' => 'بنسان',
                'plural_few' => 'بنسات',
                'plural' => 'بنس',
            ],
        ];

        return $subCurrencies[$currency] ?? $subCurrencies['EGP'];
    }
}
