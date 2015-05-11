<?php

namespace %BUNDLENAMESPACE%\Controller;

use \Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use \SanSIS\CrudBundle\Controller\ControllerCrudAbstract;

/**
 *******************************************************
 * ATENÇÃO: REGRAS DE NEGÓCIO FICAM NA CAMADA SERVICE! *
 *******************************************************
 * Controller para a service %ENTITYNAMELOWER%.service.*
 *******************************************************
 * Utiliza o CrudBundle para criação de CRUDS.         *
 * Os métodos abaixo podem ser sobrescritos conforme   *
 * a necessidade do caso de uso (Controle de acesso,   *
 * disponibilidade da ação de acordo com status do     *
 * item, etc).                                         *
 *                                                     *
 * Deve-se também sobrescrever qualquer action que não *
 * deva estar disponível.                              *
 *******************************************************
 * @Route("/%ENTITYNAMELOWER%")
 ******************************************************/
class %ENTITYNAME%Controller extends ControllerCrudAbstract
{
    /**
     * {@inheritdoc}
     */
    public function setExportHeader($arr)
    {
        return  parent::setExportHeader($arr);
    }

    /**
     * {@inheritdoc}
     */
    public function getGridActions($id, $item = null)
    {
        return parent::getGridActions($id, $item);
    }

    /**
     * {@inheritdoc}
     */
    public function getEditGridAction($id, $item = null)
    {
        return parent::getEditGridAction($id, $item);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewGridAction($id, $item = null)
    {
        return parent::getViewGridAction($id, $item);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleteGridAction($id, $item = null)
    {
        return parent::getDeleteGridAction($id, $item);
    }
}
