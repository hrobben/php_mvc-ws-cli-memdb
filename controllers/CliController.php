<?php

class CliController extends Controller
{
    public function actionIndex($out = '')
    {
        $articleModel = new ArticleModel();
        $articles = $articleModel->getAll();

        $templateEngine = new TemplateEngine();
        return $templateEngine->render(
            ('cli/cli.html'),
            array(
                'articles' => $articles,
                'out' => $out,
            )
        );
    }

    public function actionView($id, $out)
    {
        $articleModel = new ArticleModel();
        $article = $articleModel->get($id);

        if ($article === false) {
            throw new Http404();
        }
        $article["out"] = $out;

        $templateEngine = new TemplateEngine();
        return $templateEngine->render('cli/cliview.html', $article);
    }

    public function actionHelp()
    {
        $templateEngine = new TemplateEngine();
        return $templateEngine->render('cli/clihelp.html');
    }
}