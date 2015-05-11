<?php
/**
 * Reescrita do código do Oracle Platform para poder fazer a reversa
 * dentro do ambiente do SanSIS - Sistemas Inteligentes. O método original não
 * retorna nenhuma tabela pois busca de sys.user_tables
 *
 * @author pablo.sanchez
 *
 */

namespace SanSIS\DevelBundle\Doctrine\DBAL\Platforms;

use \Doctrine\DBAL\Platforms\OraclePlatform;
use \Doctrine\DBAL\Schema\Table;

/**
 * OraclePlatform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class SysAllOraclePlatform extends OraclePlatform
{

    public function getListTablesSQL($database = null)
    {
        return "SELECT * FROM sys.all_tables where OWNER = '" . strtoupper($database) . "'";
    }

    /**
     * {@inheritDoc}
     *
     * @license New BSD License
     * @link http://ezcomponents.org/docs/api/trunk/DatabaseSchema/ezcDbSchemaOracleReader.html
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        $table = strtoupper($table);

        return "SELECT uind.index_name AS name, " .
        "       uind.index_type AS type, " .
        "       decode( uind.uniqueness, 'NONUNIQUE', 0, 'UNIQUE', 1 ) AS is_unique, " .
        "       uind_col.column_name AS column_name, " .
        "       uind_col.column_position AS column_pos, " .
        "       (SELECT ucon.constraint_type FROM sys.all_constraints ucon WHERE ucon.constraint_name = uind.index_name" .
        "  		AND OWNER = '" . strtoupper($database) . "' ) AS is_primary " .
        "FROM sys.all_indexes uind, sys.all_ind_columns uind_col " .
        "WHERE uind.index_name = uind_col.index_name AND uind_col.table_name = '$table' " .
        "AND OWNER = '" . strtoupper($database) . "' " .
        "ORDER BY uind_col.column_position ASC";
    }

    /**
     * {@inheritDoc}
     */
    public function getListViewsSQL($database)
    {
        return "SELECT view_name, text FROM sys.all_views WHERE OWNER = '" . strtoupper($database) . "'";
    }

    public function getListTableForeignKeysSQL($table, $database = null)
    {
        $table = strtoupper($table);

        return "SELECT alc.constraint_name,
          alc.DELETE_RULE,
          alc.search_condition,
          cols.column_name \"local_column\",
          cols.position,
          r_alc.table_name \"references_table\",
          r_cols.column_name \"foreign_column\"
     FROM sys.all_cons_columns cols
LEFT JOIN sys.all_constraints alc
       ON alc.constraint_name = cols.constraint_name
LEFT JOIN sys.all_constraints r_alc
       ON alc.r_constraint_name = r_alc.constraint_name
LEFT JOIN sys.all_cons_columns r_cols
       ON r_alc.constraint_name = r_cols.constraint_name
      AND cols.position = r_cols.position
    WHERE alc.constraint_name = cols.constraint_name
      AND alc.constraint_type = 'R'
      AND alc.OWNER = '" . strtoupper($database) . "'
      AND alc.table_name = '" . $table . "'";
    }

    public function getListTableConstraintsSQL($table)
    {
        $table = strtoupper($table);
        return "SELECT * FROM sys.all_constraints WHERE table_name = '" . $table . "' AND OWNER = '" . strtoupper($database) . "'";
    }

    public function getListTableColumnsSQL($table, $database = null)
    {
        $table = strtoupper($table);

        $tabColumnsTableName = "sys.all_tab_columns";
        $ownerCondition = '';

        if (null !== $database) {
            $database = strtoupper($database);
            $tabColumnsTableName = "all_tab_columns";
            $ownerCondition = "AND c.owner = '" . $database . "'";
        }

        return "SELECT c.*, d.comments FROM $tabColumnsTableName c " .
        "INNER JOIN sys.all_col_comments d ON d.TABLE_NAME = c.TABLE_NAME AND d.COLUMN_NAME = c.COLUMN_NAME " .
        "WHERE c.table_name = '" . $table . "' " . $ownerCondition . " ORDER BY c.column_name";
    }
}
