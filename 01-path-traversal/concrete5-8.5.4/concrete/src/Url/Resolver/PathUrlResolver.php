<?php

namespace Concrete\Core\Url\Resolver;

use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Application\Service\Dashboard;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Page\Page;
use Concrete\Core\Url\Components\Path;
use Concrete\Core\Url\UrlInterface;
use League\Url\Url;

class PathUrlResolver implements UrlResolverInterface, ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * @var \Concrete\Core\Url\Resolver\CanonicalUrlResolver
     */
    protected $canonical;

    /**
     * @var \Concrete\Core\Application\Service\Dashboard
     */
    protected $dashboard;

    /**
     * PathUrlResolver constructor.
     *
     * @param \Concrete\Core\Config\Repository\Repository $repository
     * @param \Concrete\Core\Url\Resolver\CanonicalUrlResolver $canonical_resolver
     * @param \Concrete\Core\Application\Service\Dashboard $dashboard
     */
    public function __construct(Repository $repository, CanonicalUrlResolver $canonical_resolver, Dashboard $dashboard)
    {
        $this->config = $repository;
        $this->canonical = $canonical_resolver;
        $this->dashboard = $dashboard;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Url\Resolver\UrlResolverInterface::resolve()
     */
    public function resolve(array $arguments, $resolved = null)
    {
        if ($resolved) {
            // We don't need to do any post processing on urls.
            return $resolved;
        }

        $page = null;
        foreach ($arguments as $key => $argument) {
            if ($argument instanceof Page) {
                $page = $argument;
                break;
            }
        }

        if ($page) {
            unset($arguments[$key]);
        }

        $args = $arguments;
        $path = array_shift($args);

        if (is_scalar($path) || (is_object($path) && method_exists($path, '__toString'))) {
            $path = rtrim($path, '/');
            $url = $this->canonical->resolve([$page]);
            $url = $this->handlePath($url, $path, $args);

            return $url;
        }

        return null;
    }

    /**
     * @param \Concrete\Core\Url\UrlInterface $url
     * @param string $path
     * @param array $args
     *
     * @return \Concrete\Core\Url\UrlInterface|\League\Url\Url
     */
    protected function handlePath(UrlInterface $url, $path, $args)
    {
        $path_object = $this->basePath($url, $path, $args);

        $components = parse_url($path);

        $reset = false;
        // Were we passed a built URL? If so, just return it.
        if ($string = array_get($components, 'scheme')) {
            try {
                $url = Url::createFromUrl($path);
                $path_object = $url->getPath();
                $reset = true;
            } catch (\Exception $e) {
            }
        }

        if (!$reset) {
            if ($string = array_get($components, 'path')) {
                $path_object->append($string);
            }
            if ($string = array_get($components, 'query')) {
                $url = $url->setQuery($string);
            }
            if ($string = array_get($components, 'fragment')) {
                $url = $url->setFragment($string);
            }
        }

        foreach ($args as $segment) {
            if (!is_array($segment)) {
                $segment = (string) $segment; // sometimes integers foul this up when we pass them in as URL arguments.
            }
            $path_object->append($segment);
        }

        if (!$reset) {
            $url_path = $url->getPath();
            $url_path->append($path_object);
        } else {
            $url_path = $path_object;
        }

        return $url->setPath($url_path);
    }

    /**
     * @param \Concrete\Core\Url\UrlInterface $url
     * @param string $path
     * @param array $args
     *
     * @return \Concrete\Core\Url\Components\Path
     */
    protected function basePath($url, $path, $args)
    {
        $config = $this->config;
        $path_object = new Path('');

        $rewriting = $config->get('concrete.seo.url_rewriting');
        $rewrite_all = $config->get('concrete.seo.url_rewriting_all');
        $in_dashboard = $this->dashboard->inDashboard($path);

        // If rewriting is disabled, or all_rewriting is disabled and we're
        // in the dashboard, add the dispatcher.
        if (!$rewriting || (!$rewrite_all && $in_dashboard)) {
            $path_object->prepend(DISPATCHER_FILENAME);
        }

        return $path_object;
    }
}
