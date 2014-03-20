<?php

namespace Coral\ContentBundle\Controller;

use Symfony\Component\Config\Definition\Exception\Exception;
use Coral\SiteBundle\Exception\CoralConnectException;
use Coral\CoreBundle\Exception\JsonException;
use Coral\CoreBundle\Utility\JsonParser;

use Coral\CoreBundle\Controller\JsonController;

abstract class ConfigurableJsonController extends JsonController
{
    protected function getConfiguration($slug, $isMandatory = false)
    {
        try {
            $node = $this->get('coral_connect')->doGetRequest('/v1/node/detail/published/' . $slug);
        }
        catch(CoralConnectException $e) {
            if($isMandatory) {
                throw $e;
            }
            return new JsonParser;
        }

        $contentItems = $node->getOptionalParam('sections.*.items');

        if($contentItems && count($contentItems)) {
            foreach ($contentItems as $contentItem) {
                if($contentItem['renderer'] == 'json') {
                    return new JsonParser($contentItem['content'], $isMandatory);
                }
            }
        }

        if($isMandatory) {
            throw new Exception("Unable to find any content items 'sections.*.items'.");
        }

        return new JsonParser;
    }
}
