// Note: Login success, export success/error messages are handled in dashboard.php inline script

// Category data for JavaScript
window.incomeCategories = <? php 
                                    $stmt = $conn -> prepare("SELECT DISTINCT name FROM categories WHERE user_id = ? AND type = 'income'");
$stmt -> bind_param("i", $user_id);
$stmt -> execute();
$income_cats = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);
                                    echo json_encode(array_column($income_cats, 'name'));
                                ?>;

window.expenseCategories = <? php 
                                    $stmt = $conn -> prepare("SELECT DISTINCT name FROM categories WHERE user_id = ? AND type = 'expense'");
$stmt -> bind_param("i", $user_id);
$stmt -> execute();
$expense_cats = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);
                                    echo json_encode(array_column($expense_cats, 'name'));
                                ?>;

// Balance and currency data for JavaScript
window.currentBalance = <? php echo $total_balance; ?>;
window.currencySymbol = '<?php echo addslashes($currency_symbol); ?>';

