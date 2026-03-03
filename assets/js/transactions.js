// Transaction-related JavaScript functionality
// This file contains reusable transaction functions

// Format currency for display
function formatCurrency(amount, symbol) {
    return `${symbol}${parseFloat(amount).toFixed(2)}`;
}

// Validate transaction amount
function validateTransactionAmount(amount, type, balance) {
    const numAmount = parseFloat(amount);

    if (isNaN(numAmount) || numAmount <= 0) {
        return {
            valid: false,
            message: 'Please enter a valid amount greater than 0'
        };
    }

    if (type === 'expense' && numAmount > balance) {
        return {
            valid: false,
            message: `Insufficient balance. Your current balance is ${formatCurrency(balance, window.currencySymbol || '$')}`
        };
    }

    return {
        valid: true,
        message: ''
    };
}

// Calculate transaction statistics
function calculateTransactionStats(transactions) {
    const stats = {
        totalIncome: 0,
        totalExpense: 0,
        balance: 0,
        count: transactions.length
    };

    transactions.forEach(transaction => {
        if (transaction.type === 'income') {
            stats.totalIncome += parseFloat(transaction.amount);
        } else if (transaction.type === 'expense') {
            stats.totalExpense += parseFloat(transaction.amount);
        }
    });

    stats.balance = stats.totalIncome - stats.totalExpense;

    return stats;
}

// Filter transactions by date range
function filterTransactionsByDateRange(transactions, startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);

    return transactions.filter(transaction => {
        const transactionDate = new Date(transaction.created_at);
        return transactionDate >= start && transactionDate <= end;
    });
}

// Filter transactions by type
function filterTransactionsByType(transactions, type) {
    if (!type || type === 'all') {
        return transactions;
    }
    return transactions.filter(transaction => transaction.type === type);
}

// Filter transactions by category
function filterTransactionsByCategory(transactions, categoryId) {
    if (!categoryId || categoryId === 'all') {
        return transactions;
    }
    return transactions.filter(transaction => transaction.category_id == categoryId);
}

// Sort transactions
function sortTransactions(transactions, sortBy = 'date', order = 'desc') {
    const sorted = [...transactions];

    sorted.sort((a, b) => {
        let comparison = 0;

        switch (sortBy) {
            case 'date':
                comparison = new Date(a.created_at) - new Date(b.created_at);
                break;
            case 'amount':
                comparison = parseFloat(a.amount) - parseFloat(b.amount);
                break;
            case 'type':
                comparison = a.type.localeCompare(b.type);
                break;
            case 'category':
                comparison = (a.category_name || '').localeCompare(b.category_name || '');
                break;
            default:
                comparison = 0;
        }

        return order === 'desc' ? -comparison : comparison;
    });

    return sorted;
}

// Check if transaction is editable (within 24 hours)
function isTransactionEditable(createdAt) {
    const transactionTime = new Date(createdAt).getTime();
    const currentTime = new Date().getTime();
    const hoursPassed = (currentTime - transactionTime) / (1000 * 60 * 60);
    return hoursPassed < 24;
}

// Export functions for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatCurrency,
        validateTransactionAmount,
        calculateTransactionStats,
        filterTransactionsByDateRange,
        filterTransactionsByType,
        filterTransactionsByCategory,
        sortTransactions,
        isTransactionEditable
    };
}
