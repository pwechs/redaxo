<?php

/**
 * @package redaxo\core
 */
class rex_clang_service
{
    /**
     * Erstellt eine Clang
     *
     * @param string $code     Clang Code
     * @param string $name     Name
     * @param int    $priority Priority
     */
    public static function addCLang($code, $name, $priority)
    {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTablePrefix() . 'clang');
        $sql->setNewId('id');
        $sql->setValue('code', $code);
        $sql->setValue('name', $name);
        $sql->setValue('priority', $priority);
        $sql->insert();
        $id = $sql->getLastId();

        rex_sql_util::organizePriorities(rex::getTable('clang'), 'priority', '', 'priority, id != ' . $id);

        rex_delete_cache();

        // ----- EXTENSION POINT
        $clang = rex_clang::get($id);
        rex_extension::registerPoint(new rex_extension_point('CLANG_ADDED', '', [
            'id'    => $clang->getId(),
            'name'  => $clang->getName(),
            'clang' => $clang
        ]));
    }

    /**
     * Ändert eine Clang
     *
     * @param int    $id       Id der Clang
     * @param string $code     Clang Code
     * @param string $name     Name der Clang
     * @param int    $priority Priority
     * @return bool
     * @throws rex_exception
     */
    public static function editCLang($id, $code, $name, $priority)
    {
        if (!rex_clang::exists($id)) {
            throw new rex_exception('clang with id "' . $id . '" does not exist');
        }

        $oldPriority = rex_clang::get($id)->getPriority();

        $editLang = rex_sql::factory();
        $editLang->setTable(rex::getTablePrefix() . 'clang');
        $editLang->setWhere(['id' => $id]);
        $editLang->setValue('code', $code);
        $editLang->setValue('name', $name);
        $editLang->setValue('priority', $priority);
        $editLang->update();

        $comparator = $oldPriority < $priority ? '=' : '!=';
        rex_sql_util::organizePriorities(rex::getTable('clang'), 'priority', '', 'priority, id' . $comparator . $id);

        rex_delete_cache();

        // ----- EXTENSION POINT
        $clang = rex_clang::get($id);
        rex_extension::registerPoint(new rex_extension_point('CLANG_UPDATED', '', [
            'id'    => $clang->getId(),
            'name'  => $clang->getName(),
            'clang' => $clang
        ]));

        return true;
    }

    /**
     * Löscht eine Clang
     *
     * @param int $id Zu löschende ClangId
     * @throws rex_exception
     */
    public static function deleteCLang($id)
    {
        $startClang = rex_clang::getStartId();
        if ($id == $startClang) {
            throw new rex_functional_exception(rex_i18n::msg('clang_error_startidcanotbedeleted', $startClang));
        }

        if (!rex_clang::exists($id)) {
            throw new rex_functional_exception(rex_i18n::msg('clang_error_idcanotbedeleted', $id));
        }

        $clang = rex_clang::get($id);

        $del = rex_sql::factory();
        $del->setQuery('delete from ' . rex::getTablePrefix() . 'clang where id=?', [$id]);

        rex_sql_util::organizePriorities(rex::getTable('clang'), 'priority', '', 'priority');

        rex_delete_cache();

        // ----- EXTENSION POINT
        rex_extension::registerPoint(new rex_extension_point('CLANG_DELETED', '', [
            'id'    => $clang->getId(),
            'name'  => $clang->getName(),
            'clang' => $clang
        ]));
    }

    /**
     * Schreibt Spracheigenschaften in die Datei include/clang.php
     *
     * @throws rex_exception
     */
    public static function generateCache()
    {
        $lg = rex_sql::factory();
        $lg->setQuery('select * from ' . rex::getTablePrefix() . 'clang order by priority');

        $clangs = [];
        foreach ($lg as $lang) {
            $id = $lang->getValue('id');
            foreach ($lg->getFieldnames() as $field) {
                $clangs[$id][$field] = $lang->getValue($field);
            }
        }

        $file = rex_path::cache('clang.cache');
        if (rex_file::putCache($file, $clangs) === false) {
            throw new rex_exception('Clang cache file could not be generated');
        }
    }
}
