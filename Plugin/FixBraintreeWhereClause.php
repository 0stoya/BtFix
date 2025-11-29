<?php
namespace Ostoya\BtFix\Plugin;

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

                if (strpos($value, '`main_table`.`main_table`.') !== false) {
                    $value = str_replace(
                        '`main_table`.`main_table`.',
                        '`main_table`.',
                        $value
                    );
                }

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
