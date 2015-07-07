<?php

namespace SanSIS\DevelBundle\Service;

use \JMS\DiExtraBundle\Annotation as DI;
use \SanSIS\DevelBundle\Service\DevelService;
use \Symfony\Component\DependencyInjection\Container;

/**
 * Class DbReverseService
 * @package SanSIS\DevelBundle\Service
 * @DI\Service("generatecrud.service")
 */
class GenerateCrudService extends DevelService
{
    protected $container;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function generateCrud()
    {
        $this->buildFoldersStructure();
        $this->createTemplates();
    }

    public function mkdir($dir)
    {
        if (!file_exists($dir)) {
            echo 'Criando diretorio ' . $dir . "\n";
            mkdir($dir);
        }
    }

    public function buildFoldersStructure()
    {
        $dto = $this->getDto();

        $targetDir = $dto->query->get('BUNDLEDIR');

        if (strstr($dto->query->get('ENTITYNAME'), '\\')) {
            $entArr = explode('\\', $dto->query->get('ENTITYNAME'));
            $dto->query->set('ENTITYNAME', $entArr[count($entArr) - 1]);
        }

        $entity = $dto->query->get('ENTITYNAME');

        $dsp = DIRECTORY_SEPARATOR;

        $this->mkdir($targetDir . 'Controller');
        $this->mkdir($targetDir . 'Service');
        // $this->mkdir($targetDir . 'Resources');
        // $this->mkdir($targetDir . 'Resources' . $dsp . 'views');
        // $this->mkdir($targetDir . $dsp . 'Resources' . $dsp . 'views' . $dsp . $entity);
    }

    public function getTemplateData()
    {
        $dto = $this->getDto();

        if ($dto->query->get('ENTITYFULLNAME')) {
            $dto->query->set('ENTITYFULLNAME', "\n\n    /**\n     * {@inheritdoc}\n     */\n    protected \$rootEntityName = '" . $dto->query->get('ENTITYFULLNAME') . "';");
        }

        // echo $this->buildForm($dto->query->get('ATTRS'));die;

        return array(
            'BUNDLENAMESPACE' => $dto->query->get('BUNDLENAMESPACE'),
            'BUNDLENAME' => $dto->query->get('BUNDLENAME'),
            'ENTITYNAME' => $dto->query->get('ENTITYNAME'),
            'ENTITYFULLNAME' => $dto->query->get('ENTITYFULLNAME'),
            'ENTITYNAMELOWER' => $dto->query->get('ENTITYNAMELOWER'),
            'FORM' => $this->buildForm($dto->query->get('ATTRS')),
        );
    }

    public function getTemplateDir()
    {
        $dsp = DIRECTORY_SEPARATOR;
        $devDir = $this->container->get('kernel')->locateResource('@DevelBundle');
        return substr($devDir, 0, strlen($devDir) - 1) . $dsp . 'Resources' . $dsp . 'crud_skeleton' . $dsp;
    }

    public function buildForm($struct, $rootNode = '', $rootNodeHtmlId = '', $rootNodeTwigPath = 'entityData')
    {
        $html = '';
        $dsp = DIRECTORY_SEPARATOR;
        $skelPath = $this->getTemplateDir();

        foreach ($struct as $node => $val) {
            if ($rootNode) {
                $nodeName = $rootNode . '[' . $node . ']';
                $nodeHtmlId = $rootNodeHtmlId . '_' . $node;
            } else {
                $nodeName = $node;
                $nodeHtmlId = $node;
            }
            $nodeTwigPath = $rootNodeTwigPath . '.' . $node;

            if (is_array($val)) {
                $html .= $this->buildForm($val, $nodeName, $nodeHtmlId, $nodeTwigPath);
            } else {
                $data = array('NODENAME' => $nodeName, 'NODEID' => $nodeHtmlId, 'NODETWIGPATH' => $nodeTwigPath);
                $html .= $this->prepareTemplate($skelPath . 'Resources' . $dsp . 'forms' . $dsp . 'input.tpl', $data);
                echo $nodeName . " => " . $nodeHtmlId . "\n";
            }
        }

        return $html;
    }

    public function createTemplates()
    {
        $dto = $this->getDto();

        $targetDir = $dto->query->get('BUNDLEDIR');
        $entity = $dto->query->get('ENTITYNAME');

        $dsp = DIRECTORY_SEPARATOR;

        $skelPath = $this->getTemplateDir();
        $data = $this->getTemplateData();
        $controllerCode = $this->prepareTemplate($skelPath . 'Controller' . $dsp . 'TemplateController.php.tpl', $data);
        $serviceCode = $this->prepareTemplate($skelPath . 'Service' . $dsp . 'TemplateService.php.tpl', $data);
        // $createCode = $this->prepareTemplate($skelPath . 'Resources' . $dsp . 'views' . $dsp . 'create.html.twig', $data);
        // $editCode = $this->prepareTemplate($skelPath . 'Resources' . $dsp . 'views' . $dsp . 'edit.html.twig', $data);
        // $formCode = $this->prepareTemplate($skelPath . 'Resources' . $dsp . 'views' . $dsp . 'form.html.twig', $data);
        // $indexCode = $this->prepareTemplate($skelPath . 'Resources' . $dsp . 'views' . $dsp . 'index.html.twig', $data);
        // $searchCode = $this->prepareTemplate($skelPath . 'Resources' . $dsp . 'views' . $dsp . 'searchform.html.twig', $data);
        // $viewCode = $this->prepareTemplate($skelPath . 'Resources' . $dsp . 'views' . $dsp . 'view.html.twig', $data);

        if (!file_exists($targetDir . 'Controller' . $dsp . $entity . 'Controller.php')) {
            echo "Criando arquivo " . $targetDir . 'Controller' . $dsp . $entity . 'Controller.php' . "\n";
            file_put_contents($targetDir . 'Controller' . $dsp . $entity . 'Controller.php', $controllerCode);
        }
        if (!file_exists($targetDir . 'Service' . $dsp . $entity . 'Service.php')) {
            echo "Criando arquivo " . $targetDir . 'Service' . $dsp . $entity . 'Service.php' . "\n";
            file_put_contents($targetDir . 'Service' . $dsp . $entity . 'Service.php', $serviceCode);
        }
        // if (!file_exists($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'create.html.twig')) {
        //     echo "Criando arquivo " . $targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'create.html.twig' . "\n";
        //     file_put_contents($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'create.html.twig', $createCode);
        // }
        // if (!file_exists($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'edit.html.twig')) {
        //     echo "Criando arquivo " . $targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'edit.html.twig' . "\n";
        //     file_put_contents($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'edit.html.twig', $editCode);
        // }
        // if (!file_exists($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'form.html.twig')) {
        //     echo "Criando arquivo " . $targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'form.html.twig' . "\n";
        //     file_put_contents($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'form.html.twig', $formCode);
        // }
        // if (!file_exists($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'index.html.twig')) {
        //     echo "Criando arquivo " . $targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'index.html.twig' . "\n";
        //     file_put_contents($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'index.html.twig', $indexCode);
        // }
        // if (!file_exists($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'searchform.html.twig')) {
        //     echo "Criando arquivo " . $targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'searchform.html.twig' . "\n";
        //     file_put_contents($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'searchform.html.twig', $searchCode);
        // }
        // if (!file_exists($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'view.html.twig')) {
        //     echo "Criando arquivo " . $targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'view.html.twig' . "\n";
        //     file_put_contents($targetDir . 'Resources' . $dsp . 'views' . $dsp . $entity . $dsp . 'view.html.twig', $viewCode);
        // }
        echo "Estrutura criada\n";
    }
}
