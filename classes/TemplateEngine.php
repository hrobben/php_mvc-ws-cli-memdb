<?php

class TemplateEngine
{
    /**
     * @param $templateName
     * @param array $templateParams
     * @return string
     * @throws Exception
     */
    public function render($templateName, array $templateParams = [])
    {
        $templatePath = BASE_PATH . '/templates/' . $templateName;

        if (!file_exists($templatePath)) {
            throw new Exception(sprintf('Could not find template: %s', $templatePath));
        }

        //$template = file_get_contents($templatePath);

        return $this->evaluateTemplate($templatePath, $templateParams);
    }

    /**
     * @param $templatePath
     * @param $templateParams
     * @return string
     * ob_ gives user beter visual load of pages. not better for php performance.
     */
    public function evaluateTemplate($templatePath, $templateParams)
    {
        extract($templateParams);

        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $templatePath;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
