<?php
namespace 0stoya\BtFix\Plugin;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class FixBraintreeWhereClause
{
    /**
     * Fix broken aliases like `main_table`.`main_table`.created_at
     * before the grid collection loads.
     */
    public function beforeLoad(
        Collection $subject,
        bool $printQuery = false,
        bool $logQuery = false
    ): array {
        $select = $subject->getSelect();

        // Parts to clean – you can add 'having' etc. if needed later.
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

                // Backticked pattern: `main_table`.`main_table`.field
                if (strpos($value, '`main_table`.`main_table`.') !== false) {
                    $value = str_replace(
                        '`main_table`.`main_table`.',
                        '`main_table`.',
                        $value
                    );
                }

                // Non-backticked pattern: main_table.main_table.field
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
