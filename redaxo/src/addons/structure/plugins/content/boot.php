<?php

/**
 * Page Content Addon
 *
 * @author markus[dot]staab[at]redaxo[dot]de Markus Staab
 *
 * @package redaxo5
 */

rex_perm::register('moveSlice[]', null, rex_perm::OPTIONS);
rex_complex_perm::register('modules', 'rex_module_perm');

if (rex::isBackend()) {
  $pages = array();

  $page = new rex_be_page_main('system', 'content', rex_i18n::msg('content'));
  $page->setRequiredPermissions('structure/hasStructurePerm');
  $page->setHidden(true);
  $subpage = new rex_be_page('edit', rex_i18n::msg('edit_mode'));
  $page->addSubPage($subpage);
  $subpage = new rex_be_page('meta', rex_i18n::msg('metadata'));
  $page->addSubPage($subpage);
  $subpage = new rex_be_page('functions', rex_i18n::msg('metafuncs'));
  $page->addSubPage($subpage);
  $pages[] = $page;

  $page = new rex_be_page_main('system', 'templates', rex_i18n::msg('templates'));
  $page->setRequiredPermissions('admin');
  $page->setPrio(30);
  $pages[] = $page;

  $page = new rex_be_page_main('system', 'modules', rex_i18n::msg('modules'));
  $page->setRequiredPermissions('admin');
  $page->setPrio(40);
  $page->addSubPage(new rex_be_page('modules', rex_i18n::msg('modules')));
  $page->addSubPage(new rex_be_page('actions', rex_i18n::msg('actions')));
  $pages[] = $page;

  $this->setProperty('pages', $pages);

  rex_extension::register('PAGE_CHECKED', function ($params) {
    if (rex_be_controller::getCurrentPagePart(1) == 'content') {
      rex_be_controller::getPageObject('structure')->setIsActive(true);
    }
  });

  if (rex_be_controller::getCurrentPagePart(1) == 'system') {
    rex_system_setting::register(new rex_system_setting_default_template_id());
  }

  rex_extension::register('CLANG_DELETED', function ($params) {
    $del = rex_sql::factory();
    $del->setQuery('delete from ' . rex::getTablePrefix() . "article_slice where clang='" . $params['clang']->getId() . "'");
  });
} else {
  rex_extension::register('FE_OUTPUT', function ($params) {
    $content = $params['subject'];

    $article = new rex_article_content;
    $article->setCLang(rex_clang::getCurrentId());

    if ($article->setArticleId(rex::getProperty('article_id'))) {
      if (rex_request::isPJAXRequest()) {
        $content .= $article->getArticle();
      } else {
        $content .= $article->getArticleTemplate();
      }
    } else {
      $content .= 'Kein Startartikel selektiert / No starting Article selected. Please click here to enter <a href="' . rex_url::backendController() . '">redaxo</a>';
      rex_response::sendPage($content);
      exit;
    }

    $art_id = $article->getArticleId();
    if ($art_id == rex::getProperty('notfound_article_id') && $art_id != rex::getProperty('start_article_id')) {
      rex_response::setStatus(rex_response::HTTP_NOT_FOUND);
    }

    // ----- inhalt ausgeben
    rex_response::sendPage($content, $article->getValue('updatedate'));
  });
}
