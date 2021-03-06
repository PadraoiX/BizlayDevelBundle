<?php

namespace SanSIS\DevelBundle\Service;

use Doctrine\ORM\EntityManager;
// use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use \Doctrine\Common\Annotations\AnnotationReader;
use \JMS\DiExtraBundle\Annotation as DI;
use \SanSIS\BizlayBundle\Service\ServiceDto;
use \SanSIS\DevelBundle\Doctrine\DBAL\Platforms\SysAllOraclePlatform;
use \SanSIS\DevelBundle\Doctrine\DBAL\Schema\SysAllOracleSchemaManager;
use \SanSIS\DevelBundle\Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use \SanSIS\DevelBundle\Service\DevelService;

/**
 * Class DbReverseService
 * @package SanSIS\DevelBundle\Service
 * @DI\Service("dbreverse.service")
 */
class DbReverseService extends DevelService
{
    protected $em;
    protected $conn;
    protected $driver;
    protected $platform;
    protected $scm;
    protected $cmf;

    /**
     * @DI\InjectParams({
     *     "dto" = @DI\Inject("servicedto"),
     *     "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "container" = @DI\Inject("service_container"),
     * })
     */
    public function __construct(ServiceDto $dto, EntityManager $entityManager = null, $container)
    {
        AnnotationReader::addGlobalIgnoredName('innerEntity');

        $this->setDto($dto);
        $this->container = $container;

        //Altera parâmetros da conexão
        $this->em = $entityManager;
        $this->conn = $entityManager->getConnection();
        if ($this->checkOracle()) {
            //Plataforma personalizada
            $this->platform = new SysAllOraclePlatform();
            //Schema manager personalizado
            $this->scm = new SysAllOracleSchemaManager($this->conn, $this->platform);

        } else {
            $this->scm = $this->conn->getSchemaManager();
            // $this->driver = $entityManager->getConfiguration()->getMetadataDriverImpl();
        }

        //obtém e define driver utilizado
        $this->driver = new DatabaseDriver($this->scm);
        $entityManager->getConfiguration()->setMetadataDriverImpl($this->driver);

        //realiza a reversa do banco
        $this->cmf = new DisconnectedClassMetadataFactory();
        $this->cmf->setEntityManager($entityManager);

        parent::__construct($dto, $entityManager);
    }

    public function checkOracle()
    {
        $params = $this->conn->getParams();
        return (bool) strstr($params['driver'], 'oci');
    }

    /**
     * Retorna lista de Schemas disponíneis no banco de dados
     *
     * @return [type] [description]
     */
    public function getDatabases()
    {
        if ($this->checkOracle()) {
            throw new \Exception('Oracle cant list databases, only current connection schemas.');
        } else {
            $dbs = $this->scm->listDatabases();
        }

        asort($dbs);

        return $dbs;
    }

    /**
     * Retorna lista de Schemas disponíneis no banco de dados
     *
     * @return [type] [description]
     */
    public function getSchemas()
    {
        if ($this->checkOracle()) {
            $schemas = $this->scm->listDatabases();
        } else {
            $schemas = $this->scm->getSchemaNames();
        }

        asort($schemas);

        return $schemas;
    }

    /**
     * Retorna a lista de Tabelas de um determinado Schema
     *
     * @return [type] [description]
     */
    public function getSchemaTables()
    {
        $schema = $this->getDto()->query->get('schema');

        if ($this->checkOracle()) {
            $this->scm->setSchema($schema);
        }

        $tables = $this->scm->listTableNames();

        sort($tables);

        return $tables;
    }

    /**
     * Realiza a reversa do banco de dados
     *
     * @return [type] [description]
     */
    public function reverseEntities()
    {
        $dsp = DIRECTORY_SEPARATOR;

        $bundleDir = $this->getDto()->query->get('bundleDir');
        $bundleName = $this->getDto()->query->get('bundleName');
        $bundleNameSpace = $this->getDto()->query->get('bundleNameSpace');
        $schema = $this->getDto()->query->get('schema');
        $tables = $this->getDto()->query->get('tables');

        $path = explode('\\', $bundleDir);
        $path = implode($dsp, $path);

        $model_dir = str_replace($dsp . $dsp, $dsp, str_replace('\\', $dsp, str_replace('/', $dsp, $path . $dsp . 'Entity' . $dsp)));
        $repos_dir = str_replace($dsp . $dsp, $dsp, str_replace('\\', $dsp, str_replace('/', $dsp, $path . $dsp . 'Repository' . $dsp)));

        $nspEntity = $bundleNameSpace . '\\Entity';
        $nspRepo = $bundleNameSpace . '\\Repository';

        echo "Verificando a existencia dos diretorios de entidades e repositorios\n";

        if (!file_exists($model_dir)) {

            echo "Criando diretorio de entidades\n";
            mkdir($model_dir);
        }

        if (!file_exists($repos_dir)) {
            echo "Criando diretorio de repositorios\n";
            mkdir($repos_dir);
        }

        echo "Configurando EntityManager para utilizacao correta com Oracle\n";

        $this->driver->setNamespace($nspEntity . '\\');

        if ($this->checkOracle()) {
            $this->scm->setSchema($schema);
        }

        $egn = new \Doctrine\ORM\Tools\EntityGenerator();
        $egn->setGenerateAnnotations(true);
        $egn->setClassToExtend('\\SanSIS\\BizlayBundle\\Entity\\AbstractEntity');
        $egn->setGenerateStubMethods(true);
        $egn->setUpdateEntityIfExists(true);

        $metadata = array();

        if (!count($tables)) {
            echo "Obtendo \"Metadata\" para as tabelas do schema\n";
            $metadata = $this->cmf->getAllMetadata();
        } else {
            echo "Obtendo \"Metadata\" para as tabelas selecionadas do schema (" . implode(', ', $tables) . ")\n";
            $class = array();
            foreach ($tables as $key => $table) {
                $arr = explode('_', $table);
                foreach ($arr as $k => $v) {
                    $arr[$k] = ucfirst(strtolower($v));
                }
                $class[$key] = $nspEntity . '\\' . implode('', $arr);
                $metadata[$key] = $this->cmf->getMetadataFor($class[$key]);
            }
        }

        echo "Processando \"Metadata\" para cada tabela\n";
        foreach ($metadata as $key => $entity) {
            echo "\nProcessando tabela {$metadata[$key]->table['name']}\n";

            echo "Corrgindo nome da entidade {$entity->name}\n";

            $currTable = $metadata[$key]->table['name'];
            $name = explode('\\', $entity->name);

            $pos = count($name) - 1;
            $class = explode('.', $name[$pos]);
            if (isset($class[1]) && $class[1]) {
                $name[$pos] = ucfirst($class[1]);
            }

            /**
             * @TODO AJUSTAR O NOME DA ENTIDADE!
             */
            $name[$pos] = $this->filterTbPrefix($name[$pos]);
            $className = $name[$pos];
            $entity->name = implode('\\', $name);

            $metadata[$key]->table = array(
                'name' => $schema . '.' .
                $metadata[$key]->table['name'],
            );

            echo "Nome corrigido da entidade: $entity->name \n";

            /**
             * Corrige o nome das colunas retirando as duas primeiras letras referentes ao tipo e colocando uc_first para todas.
             */
            foreach ($metadata[$key]->fieldMappings as $k => $v) {
                if (strpos(strtolower($v['columnName']), 'fl_') === 0) {
                    $metadata[$key]->fieldMappings[$k]['fieldName'] = 'is' . substr($v['fieldName'], 2);
                } else if (strpos($v['columnName'], '_') == 2) {
                    $metadata[$key]->fieldMappings[$k]['fieldName'] = lcfirst(substr($v['fieldName'], 2));
                }
            }

            /**
             * Alterando o nome da PK da tabela para id no objeto.
             */
            $id = $metadata[$key]->identifier[0];
            $metadata[$key]->identifier[0] = 'id';
            $metadata[$key]->fieldMappings[$id]['fieldName'] = 'id';
            $bkp = $metadata[$key]->fieldMappings[$id];
            if (!isset($bkp['columnName'])) {
                throw new \Exception('Não é possível criar o nome da Sequence. Verifique a modelagem da tabela ' . $schema . '.' . $currTable);
            }
            unset($metadata[$key]->fieldMappings[$id]);
            $metadata[$key]->fieldMappings['id'] = $bkp;

            /**
             * @TODO: colocar opção para sequence (se usa ou não um método externo para criar, etc)
             */
            // $sequenceName = $this->generateSequenceName($schema, $currTable, $bkp['columnName']);
            // $metadata[$key]->idGenerator = new SequenceGenerator($sequenceName, 1);
            // $metadata[$key]->sequenceGeneratorDefinition['sequenceName'] = $sequenceName;
            //
            $metadata[$key]->idGenerator = new AssignedGenerator();

            echo "Corrigindo o nome das entidades em relacionamentos\n";
            foreach ($metadata[$key]->associationMappings as $assk => $ass) {

                if (strpos($ass['joinColumns'][0]['name'], '_') == 2) {
                    $metadata[$key]->associationMappings[$assk]['fieldName'] = lcfirst(substr($ass['fieldName'], 2));
                }

                $name = explode('\\', $metadata[$key]->associationMappings[$assk]['targetEntity']);
                $pos = count($name) - 1;
                $class = explode('.', $name[$pos]);
                if (isset($class[1]) && $class[1]) {
                    $name[$pos] = $class[0] . ucfirst($class[1]);
                }
                $name[$pos] = $this->filterTbPrefix($name[$pos]);

                $metadata[$key]->associationMappings[$assk]['targetEntity'] = implode('\\', $name);

                $name = explode('\\', $metadata[$key]->associationMappings[$assk]['sourceEntity']);
                $pos = count($name) - 1;
                $class = explode('.', $name[$pos]);
                if (isset($class[1]) && $class[1]) {
                    $name[$pos] = $class[0] . ucfirst($class[1]);
                }
                $name[$pos] = $this->filterTbPrefix($name[$pos]);

                $metadata[$key]->associationMappings[$assk]['sourceEntity'] = implode('\\', $name);
            }

            // echo "Corrigindo o nome dos atributos (remocao de nome do banco)\n";

            echo "Gerando arquivo da entidade\n";

            $egn->writeEntityClass($entity, str_replace(str_replace('\\', $dsp, $nspEntity), '', $model_dir));

            //define o repository default da classe e ativa o lifecycles
            $entityCode = file_get_contents($model_dir . $className . ".php");
            if (!strstr($entityCode, 'HasLifecycleCallbacks')) {
                $entityCode = str_replace(
                    "@ORM\\Entity",
                    "@ORM\\Entity(repositoryClass=\"\\$nspRepo\\$className\")\n * @ORM\\HasLifecycleCallbacks()\n * @Doctrine\\Common\\Annotations\\Annotation\\IgnoreAnnotation(\"innerEntity\")",
                    $entityCode
                );
            }

            /**
             * Adiciona o necessário para obter o json schema da entidade com o Knp\JsonSchemaBundle
             */
            if (!strstr($entityCode, 'JsonSchemaBundle')) {
                echo "Adicionando JsonSchemaBundle para geração de json schemas das entidades\n";
                $entityCode = str_replace(
                    "use Doctrine\\ORM\\Mapping as ORM;",
                    "use Doctrine\\ORM\\Mapping as ORM;\nuse Knp\\JsonSchemaBundle\\Annotations as JSON;",
                    $entityCode
                );
                $entityCode = str_replace(
                    "@Doctrine\\Common\\Annotations\\Annotation\\IgnoreAnnotation(\"innerEntity\")",
                    "@Doctrine\\Common\\Annotations\\Annotation\\IgnoreAnnotation(\"innerEntity\")\n * @JSON\\Schema",
                    $entityCode
                );
            }

            /**
             * Corrige a entidade gerada para sempre usar o método identity
             */
            $seqStr = '* @ORM\\GeneratedValue(strategy="SEQUENCE")';
            if (strstr($entityCode, $seqStr)) {
                echo "Corrigindo o a estratégia da sequence para IDENTITY\n";
                $pos = strpos($entityCode, $seqStr);
                $begin = substr($entityCode, 0, $pos);
                $end = substr($entityCode, $pos + strlen($seqStr));
                $pos = strpos($end, "*/");
                $end = substr($end, $pos);
                $entityCode = $begin . "* @ORM\\GeneratedValue(strategy=\"IDENTITY\")\n     " . $end;
                // die($begin);
                // $entityCode = str_replace(
                //     "use Doctrine\\ORM\\Mapping as ORM;",
                //     "use Doctrine\\ORM\\Mapping as ORM;\nuse Knp\\JsonSchemaBundle\\Annotations as JSON;",
                //     $entityCode
                // );
                // $entityCode = str_replace(
                //     "@Doctrine\\Common\\Annotations\\Annotation\\IgnoreAnnotation(\"innerEntity\")",
                //     "@Doctrine\\Common\\Annotations\\Annotation\\IgnoreAnnotation(\"innerEntity\")\n * @JSON\\Schema",
                //     $entityCode
                // );
            }

            echo "Definindo o repositorio padrao da Entidade e adicionando Lifecycle Callbacks\n";

            $entityCode = str_replace('private', 'protected', $entityCode);

            file_put_contents($model_dir . $className . ".php", $entityCode);

            echo 'Criado arquivo ' . $model_dir . $className . ".php\n";

            //cria o repository se já não existir
            echo "Criando o repositorio da Entidade, caso ja nao exista\n";
            if (!file_exists($repos_dir . $className . '.php')) {
                $repo = $this->generateClassSkeleton(
                    $nspRepo,
                    $className,
                    '\\SanSIS\\BizlayBundle\\Repository\\AbstractRepository',
                    null,
                    array(
                        '\Doctrine\\ORM\\Query',
                    )
                );
                file_put_contents($repos_dir . $className . '.php', $repo);
                echo 'Criado arquivo ' . $repos_dir . $className . ".php\n";
            }
        }

        exec('php app' . $dsp . 'console doctrine:generate:entities ' . $bundleName);

        //Corrige a definição do isValid()
        foreach ($metadata as $key => $entity) {
            $name = explode('\\', $entity->name);

            $pos = count($name) - 1;
            $class = explode('.', $name[$pos]);
            if (isset($class[1]) && $class[1]) {
                $name[$pos] = ucfirst($class[1]);
            }
            $className = $name[$pos];

            $entityCode = file_get_contents($model_dir . $className . ".php");
            $entityCode = str_replace("isValid()\n    {\n        // Add your code here\n    }",
                "isValid()\n    {\n        parent::isValid();\n    }",
                $entityCode);

            file_put_contents($model_dir . $className . ".php", $entityCode);
            echo "isValid da entidade corrigido\n";
        }

    }

    public function filterTbPrefix($name)
    {
        $ips = $this->getDto()->query->get('ignore-prefix');
        $ips[] = 'Tb';
        foreach ($ips as $ip) {
            if (strpos($name, $ip) == 0) {
                return str_replace($ip, '', $name);
            }
        }
    }

    /**
     * Regra de nomenclatura das sequences
     *
     * @param  [type] $schema     [description]
     * @param  [type] $table      [description]
     * @param  [type] $columnName [description]
     * @return [type]             [description]
     */
    public function generateSequenceName($schema, $table, $columnName)
    {
        $seqName = strtoupper(
            'sq_' .
            str_replace('_', '', substr($table, 2)) .
            '_' .
            str_replace('_', '', $columnName)
        );

        if (strlen($seqName) > 30) {
            echo "FALHA: Sequence com mais de 30 caracteres: " . $seqName . "\n";
            $seqName = strtoupper(
                'sq_' .
                str_replace('_', '', substr($table, 2)) .
                '_' .
                str_replace(array('_', 'A', 'E', 'I', 'O', 'U'), '', $columnName)
            );
        }

        if (strlen($seqName) > 30) {
            echo "FALHA: Sequence ainda com mais de 30 caracteres: " . $seqName . "\n";
            $seqName = substr($seqName, 0, 30);
        }

        echo "Nome esperado para Sequence (com schema): " . $schema . '.' . $seqName . "\n";

        return $schema . '.' . $seqName;
    }
}
