/**
 * Composable for formatting phone numbers in Pakistani format
 * Format: XXXX-XXXXXXX (4 digits prefix, 7 digits local)
 */
export function usePhoneFormatter() {
    const formatPhone = (value: string): string => {
        // Remove all non-digits
        const digits = value.replace(/\D/g, '');
        
        // Format with dashes: XXXX-XXXXXXX
        if (digits.length <= 4) {
            return digits;
        } else if (digits.length <= 11) {
            return `${digits.slice(0, 4)}-${digits.slice(4)}`;
        } else {
            return `${digits.slice(0, 4)}-${digits.slice(4, 11)}`;
        }
    };
    
    const isValidPhone = (value: string): boolean => {
        const digits = value.replace(/\D/g, '');
        return digits.length >= 10 && digits.length <= 15;
    };
    
    return {
        formatPhone,
        isValidPhone,
    };
}

/**
 * Composable for formatting CNIC/B-Form numbers
 * Format: XXXXX-XXXXXXX-X (5 digits, 7 digits, 1 digit)
 */
export function useCnicFormatter() {
    const formatCnic = (value: string): string => {
        // Remove all non-digits
        const digits = value.replace(/\D/g, '');
        
        // Format with dashes: XXXXX-XXXXXXX-X
        if (digits.length <= 5) {
            return digits;
        } else if (digits.length <= 12) {
            return `${digits.slice(0, 5)}-${digits.slice(5, 12)}`;
        } else if (digits.length <= 13) {
            return `${digits.slice(0, 5)}-${digits.slice(5, 12)}-${digits.slice(12, 13)}`;
        } else {
            return `${digits.slice(0, 5)}-${digits.slice(5, 12)}-${digits.slice(12, 13)}`;
        }
    };
    
    const isValidCnic = (value: string): boolean => {
        const digits = value.replace(/\D/g, '');
        return digits.length === 13;
    };
    
    return {
        formatCnic,
        isValidCnic,
    };
}

/**
 * Composable for calculating age from date of birth
 */
export function useAgeCalculator() {
    const calculateAge = (dobString: string | undefined | null): string => {
        if (!dobString) return '';
        
        const dob = new Date(dobString);
        const today = new Date();
        
        // Check if date is valid
        if (isNaN(dob.getTime())) return '';
        
        // Check if DOB is in the future
        if (dob > today) return 'Invalid (future date)';
        
        let years = today.getFullYear() - dob.getFullYear();
        let months = today.getMonth() - dob.getMonth();
        let days = today.getDate() - dob.getDate();
        
        // Adjust for negative days
        if (days < 0) {
            months--;
            const prevMonth = new Date(today.getFullYear(), today.getMonth(), 0);
            days += prevMonth.getDate();
        }
        
        // Adjust for negative months
        if (months < 0) {
            years--;
            months += 12;
        }
        
        // Build age string
        const parts: string[] = [];
        if (years > 0) parts.push(`${years} year${years !== 1 ? 's' : ''}`);
        if (months > 0) parts.push(`${months} month${months !== 1 ? 's' : ''}`);
        if (days > 0 && years === 0) parts.push(`${days} day${days !== 1 ? 's' : ''}`);
        
        return parts.length > 0 ? parts.join(', ') : '0 days';
    };
    
    const isValidAge = (dobString: string, minAge: number = 3, maxAge: number = 25): boolean => {
        const ageStr = calculateAge(dobString);
        if (!ageStr || ageStr === 'Invalid (future date)') return false;
        
        const yearsMatch = ageStr.match(/(\d+)\s*year/);
        if (yearsMatch) {
            const years = parseInt(yearsMatch[1]);
            return years >= minAge && years <= maxAge;
        }
        return false;
    };
    
    return {
        calculateAge,
        isValidAge,
    };
}

/**
 * Composable for validating uniqueness of phone numbers across multiple fields
 */
export function usePhoneUniqueness() {
    const validatePhoneUniqueness = (
        fatherPhone: string,
        motherPhone: string,
        motherPhoneDifferent: boolean,
        otherPhone: string
    ): string | null => {
        const fatherDigits = fatherPhone.replace(/\D/g, '');
        const motherDigits = motherPhone.replace(/\D/g, '');
        const otherDigits = otherPhone.replace(/\D/g, '');
        
        // Only validate if mother phone is enabled and filled
        if (motherPhoneDifferent && motherDigits && fatherDigits) {
            if (motherDigits === fatherDigits) {
                return 'Mother phone cannot be the same as Father phone';
            }
        }
        
        // Check other guardian phone uniqueness
        if (otherDigits && fatherDigits && otherDigits === fatherDigits) {
            return 'Guardian phone cannot be the same as Father phone';
        }
        
        if (motherPhoneDifferent && motherDigits && otherDigits && otherDigits === motherDigits) {
            return 'Guardian phone cannot be the same as Mother phone';
        }
        
        return null;
    };
    
    return {
        validatePhoneUniqueness,
    };
}

/**
 * Unified composable for all formatting utilities
 */
export function useFormatters() {
    const phoneFormatter = usePhoneFormatter();
    const cnicFormatter = useCnicFormatter();
    const ageCalculator = useAgeCalculator();
    const phoneUniqueness = usePhoneUniqueness();
    
    return {
        ...phoneFormatter,
        ...cnicFormatter,
        ...ageCalculator,
        ...phoneUniqueness,
    };
}
