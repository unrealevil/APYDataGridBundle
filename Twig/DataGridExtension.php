<?php

namespace APY\DataGridBundle\Twig;

use APY\DataGridBundle\Grid\Grid;
use Pagerfanta\Adapter\NullAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Template;
use Twig\TwigFunction;

class DataGridExtension extends AbstractExtension implements GlobalsInterface
{
    public const DEFAULT_TEMPLATE = 'APYDataGridBundle::blocks.html.twig';

    /**
     * @var Template[]
     */
    protected $templates = [];

    /**
     * @var string
     */
    protected $theme;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var array
     */
    protected $names;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $pagerFantaDefs;

    /**
     * @var string
     */
    protected $defaultTemplate;

    /**
     * @param RouterInterface $router
     * @param string          $defaultTemplate
     */
    public function __construct($router, $defaultTemplate)
    {
        $this->router = $router;
        $this->defaultTemplate = $defaultTemplate;
    }

    public function setPagerFanta(array $def)
    {
        $this->pagerFantaDefs = $def;
    }

    public function getGlobals(): array
    {
        return [
            'grid' => null,
            'column' => null,
            'row' => null,
            'value' => null,
            'submitOnChange' => null,
            'withjs' => true,
            'pagerfanta' => false,
            'op' => 'eq',
        ];
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('grid', [$this, 'getGrid'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_html', [$this, 'getGridHtml'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_url', [$this, 'getGridUrl'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_filter', [$this, 'getGridFilter'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_column_operator', [$this, 'getGridColumnOperator'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_cell', [$this, 'getGridCell'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_search', [$this, 'getGridSearch'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_pager', [$this, 'getGridPager'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_pagerfanta', [$this, 'getPagerfanta'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_*', [$this, 'getGrid_'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * @param string $id
     */
    public function initGrid($grid, $theme = null, $id = '', array $params = [])
    {
        $this->theme = $theme;
        $this->templates = [];

        $this->names[$grid->getHash()] = ('' == $id) ? $grid->getId() : $id;
        $this->params = $params;
    }

    /**
     * Render grid block.
     *
     * @param Grid   $grid
     * @param string $theme
     * @param string $id
     *
     * @return string
     */
    public function getGrid(Environment $environment, $grid, $theme = null, $id = '', array $params = [], $withjs = true)
    {
        $this->initGrid($grid, $theme, $id, $params);

        // For export
        $grid->setTemplate($theme);

        return $this->renderBlock($environment, 'grid', ['grid' => $grid, 'withjs' => $withjs]);
    }

    /**
     * Render grid block (html only).
     *
     * @param Grid   $grid
     * @param string $theme
     * @param string $id
     *
     * @return string
     */
    public function getGridHtml(Environment $environment, $grid, $theme = null, $id = '', array $params = [])
    {
        return $this->getGrid($environment, $grid, $theme, $id, $params, false);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getGrid_(Environment $environment, $name, $grid)
    {
        return $this->renderBlock($environment, 'grid_'.$name, ['grid' => $grid]);
    }

    /**
     *
     * @return string
     */
    public function getGridPager(Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_pager', ['grid' => $grid, 'pagerfanta' => $this->pagerFantaDefs['enable']]);
    }

    /**
     * Cell Drawing override.
     *
     * @param \APY\DataGridBundle\Grid\Column\Column $column
     * @param \APY\DataGridBundle\Grid\Row           $row
     * @param Grid                                   $grid
     *
     * @return string
     */
    public function getGridCell(Environment $environment, $column, $row, $grid)
    {
        $value = $column->renderCell($row->getField($column->getId()), $row, $this->router);

        $id = $this->names[$grid->getHash()];

        if (('' != $id && ($this->hasBlock($environment, $block = 'grid_'.$id.'_column_'.$column->getRenderBlockId().'_cell')
                    || $this->hasBlock($environment, $block = 'grid_'.$id.'_column_'.$column->getType().'_cell')
                    || $this->hasBlock($environment, $block = 'grid_'.$id.'_column_'.$column->getParentType().'_cell')
                    || $this->hasBlock($environment, $block = 'grid_'.$id.'_column_id_'.$column->getRenderBlockId().'_cell')
                    || $this->hasBlock($environment, $block = 'grid_'.$id.'_column_type_'.$column->getType().'_cell')
                    || $this->hasBlock($environment, $block = 'grid_'.$id.'_column_type_'.$column->getParentType().'_cell')))
            || $this->hasBlock($environment, $block = 'grid_column_'.$column->getRenderBlockId().'_cell')
            || $this->hasBlock($environment, $block = 'grid_column_'.$column->getType().'_cell')
            || $this->hasBlock($environment, $block = 'grid_column_'.$column->getParentType().'_cell')
            || $this->hasBlock($environment, $block = 'grid_column_id_'.$column->getRenderBlockId().'_cell')
            || $this->hasBlock($environment, $block = 'grid_column_type_'.$column->getType().'_cell')
            || $this->hasBlock($environment, $block = 'grid_column_type_'.$column->getParentType().'_cell')
        ) {
            return $this->renderBlock($environment, $block, ['grid' => $grid, 'column' => $column, 'row' => $row, 'value' => $value]);
        }

        return $this->renderBlock($environment, 'grid_column_cell', ['grid' => $grid, 'column' => $column, 'row' => $row, 'value' => $value]);
    }

    /**
     * Filter Drawing override.
     *
     * @param \APY\DataGridBundle\Grid\Column\Column $column
     * @param Grid                                   $grid
     *
     * @return string
     */
    public function getGridFilter(Environment $environment, $column, $grid, $submitOnChange = true)
    {
        $id = $this->names[$grid->getHash()];

        if (('' != $id && ($this->hasBlock($environment, $block = 'grid_'.$id.'_column_'.$column->getRenderBlockId().'_filter')
                    || $this->hasBlock($environment, $block = 'grid_'.$id.'_column_id_'.$column->getRenderBlockId().'_filter')
                    || $this->hasBlock($environment, $block = 'grid_'.$id.'_column_type_'.$column->getType().'_filter')
                    || $this->hasBlock($environment, $block = 'grid_'.$id.'_column_type_'.$column->getParentType().'_filter'))
                || $this->hasBlock($environment, $block = 'grid_'.$id.'_column_filter_type_'.$column->getFilterType()))
            || $this->hasBlock($environment, $block = 'grid_column_'.$column->getRenderBlockId().'_filter')
            || $this->hasBlock($environment, $block = 'grid_column_id_'.$column->getRenderBlockId().'_filter')
            || $this->hasBlock($environment, $block = 'grid_column_type_'.$column->getType().'_filter')
            || $this->hasBlock($environment, $block = 'grid_column_type_'.$column->getParentType().'_filter')
            || $this->hasBlock($environment, $block = 'grid_column_filter_type_'.$column->getFilterType())
        ) {
            return $this->renderBlock($environment, $block, ['grid' => $grid, 'column' => $column, 'submitOnChange' => $submitOnChange && $column->isFilterSubmitOnChange()]);
        }

        return '';
    }

    /**
     * Column Operator Drawing override.
     *
     * @param \APY\DataGridBundle\Grid\Column\Column $column
     * @param Grid                                   $grid
     *
     * @return string
     */
    public function getGridColumnOperator(Environment $environment, $column, $grid, $operator, $submitOnChange = true)
    {
        return $this->renderBlock($environment, 'grid_column_operator', ['grid' => $grid, 'column' => $column, 'submitOnChange' => $submitOnChange, 'op' => $operator]);
    }

    /**
     * @param string                                 $section
     * @param Grid                                   $grid
     * @param \APY\DataGridBundle\Grid\Column\Column $param
     *
     * @return string
     */
    public function getGridUrl($section, $grid, $param = null)
    {
        $prefix = $grid->getRouteUrl().(\strpos($grid->getRouteUrl(), '?') ? '&' : '?').$grid->getHash().'[';

        switch ($section) {
            case 'order':
                if ($param->isSorted()) {
                    return $prefix.Grid::REQUEST_QUERY_ORDER.']='.$param->getId().'|'.('asc' == $param->getOrder() ? 'desc' : 'asc');
                } else {
                    return $prefix.Grid::REQUEST_QUERY_ORDER.']='.$param->getId().'|asc';
                }
                // no break
            case 'page':
                return $prefix.Grid::REQUEST_QUERY_PAGE.']='.$param;
            case 'limit':
                return $prefix.Grid::REQUEST_QUERY_LIMIT.']=';
            case 'reset':
                return $prefix.Grid::REQUEST_QUERY_RESET.']=';
            case 'export':
                return $prefix.Grid::REQUEST_QUERY_EXPORT.']='.$param;
        }
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public function getGridSearch(Environment $environment, $grid, $theme = null, $id = '', array $params = [])
    {
        $this->initGrid($grid, $theme, $id, $params);

        return $this->renderBlock($environment, 'grid_search', ['grid' => $grid]);
    }

    public function getPagerfanta($grid)
    {
        $adapter = new NullAdapter($grid->getTotalCount());

        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($grid->getLimit());
        $pagerfanta->setCurrentPage($grid->getPage() + 1);

        $url = $this->getGridUrl('page', $grid, '');
        $routeGenerator = static function($page) use ($url) {
            return \sprintf('%s%d', $url, $page - 1);
        };

        $view = new $this->pagerFantaDefs['view_class']();
        $html = $view->render($pagerfanta, $routeGenerator, $this->pagerFantaDefs['options']);

        return $html;
    }

    /**
     * Render block.
     *
     * @param string $name
     * @param array  $parameters
     *
     * @throws \InvalidArgumentException If the block could not be found
     *
     * @return string
     */
    protected function renderBlock(Environment $environment, $name, $parameters)
    {
        foreach ($this->getTemplates($environment) as $template) {
            if ($template->hasBlock($name, [])) {
                return $template->renderBlock($name, \array_merge($environment->getGlobals(), $parameters, $this->params));
            }
        }

        throw new \InvalidArgumentException(\sprintf('Block "%s" doesn\'t exist in grid template "%s".', $name, $this->theme));
    }

    /**
     * Has block.
     *
     * @param $name string
     *
     * @return bool
     */
    protected function hasBlock(Environment $environment, $name)
    {
        foreach ($this->getTemplates($environment) as $template) {
            /** @var $template Template */
            if ($template->hasBlock($name, [])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Template Loader.
     *
     * @throws \Exception
     *
     * @return Template[]
     */
    protected function getTemplates(Environment $environment)
    {
        if (empty($this->templates)) {
            if ($this->theme instanceof Template) {
                $this->templates[] = $this->theme;
                $this->templates[] = $environment->loadTemplate($environment->getTemplateClass($this->defaultTemplate), $this->defaultTemplate);
            } elseif (\is_string($this->theme)) {
                $this->templates = $this->getTemplatesFromString($environment, $this->theme);
            } elseif (null === $this->theme) {
                $this->templates = $this->getTemplatesFromString($environment, $this->defaultTemplate);
            } else {
                throw new \Exception('Unable to load template');
            }
        }

        return $this->templates;
    }

    /**
     *
     * @return array|Template[]
     */
    protected function getTemplatesFromString(Environment $environment, $theme)
    {
        $this->templates = [];

        $template = $environment->loadTemplate($environment->getTemplateClass($this->theme), $theme);
        while ($template instanceof Template) {
            $this->templates[] = $template;
            $template = $template->getParent([]);
        }

        return $this->templates;
    }
}
