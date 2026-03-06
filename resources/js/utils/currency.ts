/**
 * Format a number as Pakistani Rupees (PKR)
 * @param amount - The numeric value to format
 * @param options - Optional Intl.NumberFormat options
 * @returns Formatted currency string like "Rs 1,234.56"
 */
export function formatCurrency(
    amount: number | string,
    options?: Intl.NumberFormatOptions
): string {
    const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    if (isNaN(numAmount)) {
        return 'Rs 0.00';
    }

    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        ...options,
    }).format(numAmount);
}

/**
 * Format a number as PKR without the currency symbol (just number)
 * @param amount - The numeric value to format
 * @returns Formatted number string like "1,234.56"
 */
export function formatCurrencyNumber(amount: number | string): string {
    const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    if (isNaN(numAmount)) {
        return '0.00';
    }

    return new Intl.NumberFormat('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numAmount);
}

/**
 * Format a number as Pakistani Rupees with "Rs" prefix (compact format)
 * @param amount - The numeric value to format
 * @returns Compact formatted string like "Rs 1.2K" or "Rs 1,234"
 */
export function formatCurrencyCompact(amount: number | string): string {
    const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    if (isNaN(numAmount)) {
        return 'Rs 0';
    }

    if (numAmount >= 1000000) {
        return `Rs ${(numAmount / 1000000).toFixed(1)}M`;
    }
    
    if (numAmount >= 1000) {
        return `Rs ${(numAmount / 1000).toFixed(1)}K`;
    }

    return `Rs ${numAmount.toLocaleString('en-PK', { maximumFractionDigits: 0 })}`;
}
