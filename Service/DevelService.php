<?php
namespace SanSIS\DevelBundle\Service;

use SanSIS\BizlayBundle\Service\AbstractService;

/**
 * @author pablo.sanchez
 *
 */
abstract class DevelService extends AbstractService
{
    protected $container;

    protected function generateClassSkeleton($namespace, $class, $extends = null, $classType = null, $use = array())
    {
        if ($classType) {
            $classType .= ' ';
        }

        $skel = "<?php\n";
        $skel .= "namespace {$namespace};\n\n";
        if (is_array($use) and count($use)) {
            foreach ($use as $u) {
                $skel .= "use $u;\n";
            }
            $skel .= "\n";
        }
        $skel .= $classType . "class {$class} extends {$extends}\n";
        $skel .= "{\n";
        $skel .= "}\n";

        return $skel;
    }

    public function prepareTemplate($templateFile, $templateData)
    {
        $template = file_get_contents($templateFile);
        foreach ($templateData as $k => $val) {
            if (!is_array($val)) {
                $template = str_replace('%' . $k . '%', $val, $template);
            } else {
                $template = str_replace('%' . $k . '%', '', $template);
            }
        }
        return $template;
    }
}
