<?php

class ArticlesController extends Controller
{
    public $msg = '';

    public function actionIndex()
    {
        $articleModel = new ArticleModel();
        $articles = $articleModel->getAll();

        $templateEngine = new TemplateEngine();
        return $templateEngine->render(
            (ISCLI||$GLOBALS['XML']||$GLOBALS['JSON']?'articles/clean.html':'articles/index.html'),
            array(
                'articles' => $articles,
            )
        );
    }

    public function actionView($id) // , $slug)     // second param is newer used, maybe future use...
    {
        $articleModel = new ArticleModel();
        $article = $articleModel->get($id);

        if ($article === false) {
            throw new Http404();
        }

        $templateEngine = new TemplateEngine();
        return $templateEngine->render((ISCLI||$GLOBALS['XML']||$GLOBALS['JSON']?'articles/cleanview.html':'articles/view.html'), $article);
    }
}
