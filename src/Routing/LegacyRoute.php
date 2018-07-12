<?php

namespace TDW\LegacyBundle\Routing;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class LegacyRoute.
 *
 * @Annotation
 */
class LegacyRoute extends Annotation
{
    public $getVars = [];
    public $postVars = [];
}
