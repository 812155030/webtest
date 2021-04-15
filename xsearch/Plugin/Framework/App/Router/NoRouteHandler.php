<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Plugin\Framework\App\Router;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Router\NoRouteHandler as NativeNoRouteHandler;
use Magento\Search\Model\QueryFactory;

class NoRouteHandler
{
    /**
     * @var \Amasty\Xsearch\Model\Config
     */
    private $config;

    public function __construct(
        \Amasty\Xsearch\Model\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param NativeNoRouteHandler $subject
     * @param $proceed
     * @param RequestInterface $request
     * @return bool
     */
    public function aroundProcess(
        NativeNoRouteHandler $subject,
        \Closure $proceed,
        RequestInterface $request
    ) {
        if ($this->isRedirectEnabled($request)) {
            $pathInfo = $this->getPathInfo($request);
            $request->setParam(QueryFactory::QUERY_VAR_NAME, $pathInfo);
            $request->setModuleName('amasty_xsearch')->setControllerName('redirect')->setActionName('index');

            return true;
        }

        return $proceed($request);
    }

    /**
     * @param RequestInterface $request
     * @return mixed|string
     */
    private function getPathInfo(RequestInterface $request)
    {
        $pathInfo = $request->getOriginalPathInfo() ?: $request->getPathInfo();
        $pathInfo = trim($pathInfo, '/');
        $pathInfo = str_replace('/', ' ', $pathInfo);
        $pathInfo = str_replace('-', ' ', $pathInfo);
        $pathInfo = str_replace('.html', '', $pathInfo);
        $pathInfo = str_replace('.htm', '', $pathInfo);

        return $pathInfo;
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    private function isRedirectEnabled(RequestInterface $request)
    {
        return $this->config->hasRedirect()
            && !$request->isAjax()
            && !preg_match('@^.*\.[a-z0-9]+(?<!.html)(?<!.htm)$@', $request->getOriginalPathInfo());
    }
}
