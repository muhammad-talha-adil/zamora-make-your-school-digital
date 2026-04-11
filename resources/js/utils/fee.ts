/**
 * Fee Module Utility Functions
 * 
 * Common utility functions for fee management
 */

/**
 * Format currency amount in Pakistani Rupees
 */
export const formatCurrency = (amount: number): string => {
    return `Rs. ${amount.toLocaleString('en-PK', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
};

/**
 * Get color class for voucher status badge
 */
export const getVoucherStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
        unpaid: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        partial: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        overdue: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        adjusted: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return colors[status] || colors.unpaid;
};

/**
 * Get label for payment method
 */
export const getPaymentMethodLabel = (method: string): string => {
    const labels: Record<string, string> = {
        cash: 'Cash',
        bank: 'Bank Transfer',
        online: 'Online Payment',
        jazzcash: 'JazzCash',
        easypaisa: 'EasyPaisa',
        cheque: 'Cheque',
    };
    return labels[method] || method;
};

/**
 * Get color class for approval status badge
 */
export const getApprovalStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[status] || colors.pending;
};

/**
 * Format discount value (percentage or fixed amount)
 */
export const formatDiscountValue = (valueType: string, value: number): string => {
    return valueType === 'percentage' ? `${value}%` : formatCurrency(value);
};

/**
 * Calculate fine amount based on fine type and days overdue
 */
export const calculateFine = (
    fineType: string,
    fineValue: number,
    daysOverdue: number,
    originalAmount: number
): number => {
    if (daysOverdue <= 0) return 0;

    switch (fineType) {
        case 'fixed':
            return fineValue;
        case 'percentage':
            return (originalAmount * fineValue) / 100;
        case 'daily_fixed':
            return fineValue * daysOverdue;
        case 'daily_percentage':
            return (originalAmount * fineValue * daysOverdue) / 100;
        default:
            return 0;
    }
};

/**
 * Calculate days between two dates
 */
export const daysBetween = (date1: Date | string, date2: Date | string): number => {
    const d1 = typeof date1 === 'string' ? new Date(date1) : date1;
    const d2 = typeof date2 === 'string' ? new Date(date2) : date2;
    const diffTime = Math.abs(d2.getTime() - d1.getTime());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

/**
 * Check if a date is overdue
 */
export const isOverdue = (dueDate: Date | string): boolean => {
    const due = typeof dueDate === 'string' ? new Date(dueDate) : dueDate;
    return due < new Date();
};

/**
 * Get fee frequency label
 */
export const getFeeFrequencyLabel = (frequency: string): string => {
    const labels: Record<string, string> = {
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        semi_annual: 'Semi-Annual',
        annual: 'Annual',
        one_time: 'One Time',
    };
    return labels[frequency] || frequency;
};

/**
 * Get fee head category label
 */
export const getFeeHeadCategoryLabel = (category: string): string => {
    const labels: Record<string, string> = {
        tuition: 'Tuition',
        admission: 'Admission',
        examination: 'Examination',
        transport: 'Transport',
        library: 'Library',
        laboratory: 'Laboratory',
        sports: 'Sports',
        miscellaneous: 'Miscellaneous',
    };
    return labels[category] || category;
};
