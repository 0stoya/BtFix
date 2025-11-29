FixBraintreeWhereClause — README
Overview

Some Magento 2 extensions (most commonly Braintree, but also other payment or reporting modules) incorrectly generate SQL conditions for the Sales → Orders admin grid.
These modules sometimes produce malformed SQL such as:

`main_table`.`main_table`.created_at >= '2024-01-01'


This leads to errors like:

SQLSTATE[42S22]: Column not found: 1054 Unknown column 'main_table.main_table.created_at'


To prevent the order grid from breaking, this plugin intercepts the grid’s SQL before it loads, detects these invalid double-prefixes, and replaces them with the correct alias.

What the Plugin Does

The plugin runs on:

Magento\Sales\Model\ResourceModel\Order\Grid\Collection::load()


Before the grid executes its SQL query, the plugin:

Reads the WHERE clause of the generated SELECT.

Scans for invalid patterns such as:

`main_table`.`main_table`.field

main_table.main_table.field

Automatically rewrites them to the valid form:

`main_table`.field

main_table.field

Writes the corrected WHERE clause back into the query.

The order grid loads normally.

This makes the admin grid resilient against third-party modules that incorrectly manipulate the alias.

Why This Fix Is Needed

Magento uses main_table as the default alias for the sales_order_grid table.

When an extension incorrectly builds filters by prepending the alias twice, the SQL becomes invalid. Example:

addFieldToFilter('main_table.created_at', ['gteq' => '2024-01-01']);


combined with an internal prefixing inside another module results in:

main_table.main_table.created_at


This breaks the order grid entirely.

Rather than modifying vendor code, the plugin provides a safe, non-intrusive fix that cleans the SQL just before execution.
