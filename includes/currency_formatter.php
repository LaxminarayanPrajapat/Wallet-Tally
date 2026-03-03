<?php
/**
 * Currency Formatter Service
 * Centralized currency formatting and symbol management
 */

class CurrencyFormatter {
    private static $currencySymbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'INR' => '₹',
        'AED' => 'د.إ',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'Fr',
        'CNY' => '¥',
        'HKD' => 'HK$',
        'SGD' => 'S$',
        'SAR' => '﷼',
        'KRW' => '₩',
        'MYR' => 'RM',
        'THB' => '฿',
        'IDR' => 'Rp',
        'PHP' => '₱',
        'VND' => '₫',
        'PKR' => '₨',
        'BDT' => '৳',
        'LKR' => '₨',
        'NPR' => '₨',
        'MMK' => 'K',
        'KHR' => '៛',
        'LAK' => '₭',
        'BND' => 'B$',
        'MVR' => 'ރ',
        'BTN' => 'Nu.',
        'MNT' => '₮',
        'AFN' => '؋'
    ];
    
    /**
     * Get currency symbol for a currency code
     * @param string $currencyCode
     * @return string
     */
    public static function getSymbol($currencyCode) {
        return self::$currencySymbols[$currencyCode] ?? $currencyCode;
    }
    
    /**
     * Get all supported currency symbols
     * @return array
     */
    public static function getAllSymbols() {
        return self::$currencySymbols;
    }
    
    /**
     * Format amount with currency symbol
     * @param float $amount
     * @param string $currency Currency code
     * @param bool $useSession Use session currency symbol if available
     * @return string
     */
    public static function format($amount, $currency, $useSession = true) {
        $formatted = number_format($amount, 2);
        
        // Get symbol from session if available and requested
        if ($useSession && isset($_SESSION['currency_symbol'])) {
            $symbol = $_SESSION['currency_symbol'];
        } else {
            $symbol = self::getSymbol($currency);
        }
        
        return $symbol . $formatted;
    }
    
    /**
     * Format currency using PHP's NumberFormatter (locale-aware)
     * @param float $amount
     * @param string $currency Currency code
     * @param string $locale Locale code (default: 'en')
     * @return string
     */
    public static function formatLocale($amount, $currency, $locale = 'en') {
        if (class_exists('NumberFormatter')) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            return $formatter->formatCurrency($amount, $currency);
        }
        
        // Fallback to simple format
        return self::format($amount, $currency, false);
    }
    
    /**
     * Update user's currency symbol in database
     * @param mysqli $conn Database connection
     * @param int $userId User ID
     * @param string $currency Currency code
     * @return bool
     */
    public static function updateUserCurrencySymbol($conn, $userId, $currency) {
        $symbol = self::getSymbol($currency);
        $stmt = $conn->prepare("UPDATE users SET currency_symbol = ? WHERE id = ?");
        $stmt->bind_param("si", $symbol, $userId);
        return $stmt->execute();
    }
    
    /**
     * Ensure user has currency symbol set
     * @param mysqli $conn Database connection
     * @param int $userId User ID
     * @param string $currency Currency code
     * @return string Currency symbol
     */
    public static function ensureUserCurrencySymbol($conn, $userId, $currency, $currentSymbol = null) {
        // If symbol is already set and not empty, return it
        if (!empty($currentSymbol)) {
            return $currentSymbol;
        }
        
        // Get symbol for currency
        $symbol = self::getSymbol($currency);
        
        // Update in database
        self::updateUserCurrencySymbol($conn, $userId, $currency);
        
        return $symbol;
    }
}

// Backward compatibility functions
function getCurrencySymbol($currency_code) {
    return CurrencyFormatter::getSymbol($currency_code);
}

function formatAmount($amount, $currency) {
    return CurrencyFormatter::format($amount, $currency);
}

function formatCurrency($amount, $currency) {
    return CurrencyFormatter::formatLocale($amount, $currency);
}
?>
 