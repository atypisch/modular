<?php
/**
 * i18n Support
 * Internationalization and localization
 */

namespace Modular;

class i18n {
    private static $locale = 'en_US';
    private static $translations = [];
    private static $textDomain = 'modular';
    
    /**
     * Set locale
     */
    public static function setLocale($locale) {
        self::$locale = $locale;
        self::loadTranslations();
    }
    
    /**
     * Get locale
     */
    public static function getLocale() {
        return self::$locale;
    }
    
    /**
     * Load translations
     */
    private static function loadTranslations() {
        $langDir = dirname(dirname(__DIR__)) . '/languages';
        $langFile = $langDir . '/' . self::$locale . '.php';
        
        if (file_exists($langFile)) {
            self::$translations = include $langFile;
        } else {
            self::$translations = [];
        }
    }
    
    /**
     * Translate string
     */
    public static function __($text, $domain = null) {
        $domain = $domain ?? self::$textDomain;
        $key = $domain . ':' . $text;
        
        return self::$translations[$key] ?? $text;
    }
    
    /**
     * Translate with context
     */
    public static function _x($text, $context, $domain = null) {
        $domain = $domain ?? self::$textDomain;
        $key = $domain . ':' . $context . ':' . $text;
        
        return self::$translations[$key] ?? $text;
    }
    
    /**
     * Plural translation
     */
    public static function _n($singular, $plural, $number, $domain = null) {
        if ($number == 1) {
            return self::__($singular, $domain);
        }
        return self::__($plural, $domain);
    }
    
    /**
     * Format date according to locale
     */
    public static function date($format, $timestamp = null) {
        $timestamp = $timestamp ?? time();
        return date($format, $timestamp);
    }
    
    /**
     * Format number according to locale
     */
    public static function number($number, $decimals = 0) {
        return number_format($number, $decimals);
    }
}

// Helper functions
if (!function_exists('__')) {
    function __($text, $domain = null) {
        return \Modular\i18n::__($text, $domain);
    }
}

if (!function_exists('_x')) {
    function _x($text, $context, $domain = null) {
        return \Modular\i18n::_x($text, $context, $domain);
    }
}

if (!function_exists('_n')) {
    function _n($singular, $plural, $number, $domain = null) {
        return \Modular\i18n::_n($singular, $plural, $number, $domain);
    }
}

