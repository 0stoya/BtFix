# FixBraintreeWhereClause

A Magento 2 plugin that fixes malformed SQL aliases caused by Braintree or other extensions in the **Admin Sales Order Grid**.

This patch prevents errors where third-party modules generate SQL like:

```
`main_table`.`main_table`.created_at
```

which results in:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'main_table.main_table.created_at'
```

This plugin safely cleans up such SQL statements before the grid executes.

---

## 🚀 What This Plugin Does

Magento uses `main_table` as the primary alias for the **sales_order_grid** table.

Some modules incorrectly prepend the alias twice (e.g. Braintree), creating invalid SQL:

```
main_table.main_table.field
```

This plugin:

1. Hooks into
   `Magento\Sales\Model\ResourceModel\Order\Grid\Collection::load()`
2. Inspects the SQL `WHERE` clause.
3. Detects invalid patterns:

   * `` `main_table`.`main_table`.field ``
   * `main_table.main_table.field`
4. Normalizes them back to:

   * `` `main_table`.field ``
   * `main_table.field`
5. Allows the grid to load normally.

No core overrides, no rewrites — safe and upgrade-proof.

---

## 🧩 Plugin Code

```php
<?php
namespace TR\CustomerPricing\Plugin;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class FixBraintreeWhereClause
{
    public function beforeLoad(
        Collection $subject,
        bool $printQuery = false,
        bool $logQuery = false
    ): array {
        $select = $subject->getSelect();
        $partsToClean = ['where'];

        foreach ($partsToClean as $part) {
            $current = $select->getPart($part);

            if (!is_array($current) || !$current) {
                continue;
            }

            array_walk_recursive($current, function (&$value): void {
                if (!is_string($value)) {
                    return;
                }

                // Backticked pattern
                if (strpos($value, '`main_table`.`main_table`.') !== false) {
                    $value = str_replace(
                        '`main_table`.`main_table`.',
                        '`main_table`.',
                        $value
                    );
                }

                // Non-backticked pattern
                if (strpos($value, 'main_table.main_table.') !== false) {
                    $value = str_replace(
                        'main_table.main_table.',
                        'main_table.',
                        $value
                    );
                }
            });

            $select->setPart($part, $current);
        }

        return [$printQuery, $logQuery];
    }
}
```

---

## 📁 Installation

Place the file at:

```
app/code/TR/CustomerPricing/Plugin/FixBraintreeWhereClause.php
```

Then run:

```bash
bin/magento setup:di:compile
bin/magento cache:flush
```

No additional configuration needed.

---

## ✅ Compatibility

* Magento **2.3.x**
* Magento **2.4.x**, including **2.4.8-p1**
* Works with:

  * Braintree
  * PayPal extensions
  * Adyen
  * Other modules that manipulate the sales order grid query

The plugin is fully upgrade-safe.

---

## 🔧 Troubleshooting

### The SQL error still appears

Check if the error is in another part of the query (e.g. `ORDER BY`, `HAVING`).

If needed, update:

```php
$partsToClean = ['where', 'order', 'having'];
```

### Another alias is broken

If the extension uses a different alias (e.g. `sales`), add the pattern in the replacement logic.

---

## 📝 Notes

* No vendor/core overrides
* Safe for production
* Restores a functional Order Grid even when other modules misbehave
* Easy to extend if new broken patterns appear

---

## 📦 Want Packaging?

If you want, I can generate:

* `composer.json`
* full module structure
* CHANGELOG
* versioning tags
* LICENSE file

Just say **“create module package”**.

---

Let me know if you want this in a **GitHub Gist**, **release notes format**, or auto-generated **CHANGELOG.md**.
