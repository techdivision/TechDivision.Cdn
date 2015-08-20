<?php
namespace TechDivision\Cdn\Fluid\ViewHelpers\Uri;

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to version 3 of the GPL license,
 * that is bundled with this package in the file LICENSE, and is
 * available online at http://www.gnu.org/licenses/gpl.txt

 * @author    Philipp Dittert <pd@techdivision.com>
 * @copyright 2015 TechDivision GmbH <info@techdivision.com>
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License, version 3 (GPL-3.0)
 * @link      https://github.com/techdivision/TechDivision.Cdn
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Service;
use TYPO3\Flow\Resource\Publishing\ResourcePublisher;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Exception\InvalidVariableException;

/**
 * @Flow\Aspect
 */
class ResourceViewHelperAspect
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * Controller Context to use
     * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var \TYPO3\Flow\Core\Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var ResourcePublisher
     */
    protected $resourcePublisher;

    /**
     * @Flow\Inject
     * @var Service
     */
    protected $i18nService;

    /**
     * Injects the settings of this package
     *
     * @param array $settings the package settings
     *
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Injects the bootstrap
     *
     * @param \TYPO3\Flow\Core\Bootstrap $bootstrap the bootstrap instance
     *
     * @return void
     */
    public function injectBootstrap(\TYPO3\Flow\Core\Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Changes the url of a Resource
     *
     * @Flow\Around("method(TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper->render())")
     * @param \TYPO3\FLOW\AOP\JoinPointInterface $joinPoint the join point
     *
     * @return string The absolute URI to the resource
     * @throws InvalidVariableException
     */
    public function aroundRender(\TYPO3\FLOW\AOP\JoinPointInterface $joinPoint)
    {
        // if cdn is enabled
        if (isset($this->settings['host'])) {
            $path = $joinPoint->getMethodArgument('path');
            $package = $joinPoint->getMethodArgument('package');
            $resource = $joinPoint->getMethodArgument('resource');
            $localize = $joinPoint->getMethodArgument('localize');

            $reflectionClass = new \ReflectionClass('\TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper');
            $reflectionProperty = $reflectionClass->getProperty('controllerContext');
            $reflectionProperty->setAccessible(true);
            $this->controllerContext = $reflectionProperty->getValue($joinPoint->getProxy());

            $uniqueHash = "";
            if ($resource !== null) {
                $uri = $this->resourcePublisher->getPersistentResourceWebUri($resource);
                if ($uri === false) {
                    $uri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'BrokenResource';
                }
            } else {
                if ($path === null) {
                    throw new InvalidVariableException('The ResourceViewHelper did neither contain a valuable "resource" nor "path" argument.', 1353512742);
                }
                if ($package === null) {
                    $package = $this->controllerContext->getRequest()->getControllerPackageKey();
                }
                if (strpos($path, 'resource://') === 0) {
                    $matches = array();
                    if (preg_match('#^resource://([^/]+)/Public/(.*)#', $path, $matches) === 1) {
                        $package = $matches[1];
                        $path = $matches[2];
                    } else {
                        throw new InvalidVariableException(sprintf('The path "%s" which was given to the ResourceViewHelper must point to a public resource.', $path), 1353512639);
                    }
                }
                if ($localize === true) {
                    $resourcePath = 'resource://' . $package . '/Public/' . $path;

                    // check if "@" is in request uri, In that case the user is currently in neos backend
                    $requestHandler = $this->bootstrap->getActiveRequestHandler();
                    if ($requestHandler instanceof \TYPO3\Flow\Http\HttpRequestHandlerInterface) {
                        if (strpos($requestHandler->getHttpRequest()->getUri(), "@") === false) {
                            $uniqueHash = filemtime($resourcePath);
                        }
                    }

                    $localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);
                    $matches = array();
                    if (preg_match('#resource://([^/]+)/Public/(.*)#', current($localizedResourcePathData), $matches) === 1) {
                        $package = $matches[1];
                        $path = $matches[2];
                    }
                }

                // if file was not found on filesystem $uniqueHash is FALSE
                if (!empty($uniqueHash) && $uniqueHash !== false) {
                    $uniqueHash = "?" . $uniqueHash;
                }

                $uri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $package . '/' . $path . $uniqueHash;
            }

            return $uri;
        } else {
            // cdn disabled. execute original code
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }
    }
}
