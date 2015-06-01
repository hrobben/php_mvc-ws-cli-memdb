<?php

class IndexController extends Controller
{
    public function actionIndex()
    {
        $templateEngine = new TemplateEngine();
        return $templateEngine->render('index/index.html', array(
            'requestParams' => array_merge(
                $_SERVER,
                $this->queryParams,
                $this->extraData
            )
        ));
    }
}
