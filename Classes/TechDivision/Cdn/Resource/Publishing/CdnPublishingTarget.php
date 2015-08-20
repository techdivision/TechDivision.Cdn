<?php
namespace TechDivision\Cdn\Resource\Publishing;

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

/**
 * Publishing target for a file system extended with a cdn functionality
 *
 * @Flow\Scope("singleton")
 */
class CdnPublishingTarget extends \TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget
{
    /**
     * Detects the (resources) base URI and stores it as a protected class variable.
     *
     * $this->resourcesPublishingPath must be set prior to calling this method.
     *
     * @return void
     */
    protected function detectResourcesBaseUri()
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if ($requestHandler instanceof \TYPO3\Flow\Http\HttpRequestHandlerInterface) {
            $uri = $this->getCdnUri($requestHandler);
        } else {
            $uri = '';
        }
        $this->resourcesBaseUri = $uri . substr($this->resourcesPublishingPath, strlen(FLOW_PATH_WEB));
    }

    /**
     * Returns ether the cdn uri or use the current request base uri
     *
     * @param \TYPO3\Flow\Http\HttpRequestHandlerInterface $requestHandler the request handler
     *
     * @return string
     */
    protected function getCdnUri(\TYPO3\Flow\Http\HttpRequestHandlerInterface $requestHandler)
    {
        $uri = $requestHandler->getHttpRequest()->getBaseUri();

        if (isset($this->settings['host'])) {
            if ($this->isFrontendRequest($requestHandler)) {
                $hostname = $this->settings['host'];

                if (isset($this->settings['schema-less']) && $this->settings['schema-less'] === true) {
                    $uri = "//" . $hostname . "/";
                } elseif ($requestHandler->getHttpRequest()->isSecure()) {
                    $uri = "https://" . $hostname . "/";
                } else {
                    $uri = "http://" . $hostname . "/";
                }
            }
        }
        return $uri;
    }

    /**
     * Returns true if request is only a simple frontend request, else false
     *
     * @param \TYPO3\Flow\Http\HttpRequestHandlerInterface|null $requestHandler the request handler
     *
     * @return bool
     */
    protected function isFrontendRequest(\TYPO3\Flow\Http\HttpRequestHandlerInterface $requestHandler = null)
    {
        // if there is no http request handler do not use cdn and query string ids
        if ($requestHandler === null) {
            $requestHandler = $this->bootstrap->getActiveRequestHandler();
            if (!$requestHandler instanceof \TYPO3\Flow\Http\HttpRequestHandlerInterface) {
                return false;
            }
        }

        // check if "@" is in request uri, In that case the user is currently in neos backend
        if (strpos($requestHandler->getHttpRequest()->getUri(), "@") !== false) {
            return false;
        }

        return true;
    }

    /**
     * Returns the web URI to be used to publish the specified persistent resource
     *
     * @param \TYPO3\Flow\Resource\Resource $resource The resource to build the URI for
     * @return string The web URI
     */
    protected function buildPersistentResourceWebUri(\TYPO3\Flow\Resource\Resource $resource)
    {
        $hash = "";
        if (isset($this->settings['host']) && $this->isFrontendRequest()) {
            $hash = filemtime($resource->getUri());
            if ($hash) {
                $hash = "?" . $hash;
            }
        }

        $filename = $resource->getFilename();
        $rewrittenFilename = ($filename === '' || $filename === null) ? '' : '/' . $this->rewriteFilenameForUri($filename);
        return $this->getResourcesBaseUri() . 'Persistent/' . $resource->getResourcePointer()->getHash() . $rewrittenFilename . $hash;
    }
}
