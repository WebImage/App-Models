<?php

namespace WebImage\Models\Templates;

use WebImage\SimpleTemplate\StringTemplate;

class TemplateRenderer
{
    private string $templateDir;

    public function __construct(string $templateDir)
    {
        $this->templateDir = rtrim($templateDir, '/\\');
    }

    /**
     * Render a template with the given variables
     *
     * @param string $templateName Name of the template file (without .php extension)
     * @param array $variables Associative array of variables to substitute
     * @return string Rendered template
     * @throws \RuntimeException If template file not found
     */
    public function render(string $templateName, array $variables): string
    {
        $templatePath = $this->templateDir . '/' . $templateName . '.tpl';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$templatePath}");
        }

        $template = file_get_contents($templatePath);

        return (new StringTemplate($template))->render($variables);
    }

    /**
     * Get the template directory
     *
     * @return string
     */
    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }

    /**
     * Check if a template exists
     *
     * @param string $templateName
     * @return bool
     */
    public function templateExists(string $templateName): bool
    {
        $templatePath = $this->templateDir . '/' . $templateName . '.php';
        return file_exists($templatePath);
    }
}