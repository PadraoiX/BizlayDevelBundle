<?php
namespace SanSIS\DevelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateRestCrudCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('sansis:generate:restcrud')
            ->setDescription('Geracao de Cruds a partir de entidades geradas pela reversa.')
            ->addArgument('targetBundle', InputArgument::REQUIRED, 'Bundle que contém a entidade sobre a qual deve ser criado o Crud.')
            ->addArgument('rootEntity', InputArgument::REQUIRED, 'Qual entidade o Crud utilizará como raiz.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $help = "                Gerador de estrutura de CRUDS REST a partir de reversa do banco de dados";
        
        $this->printBrasil($help, $output);
        
        $service = $this->getContainer()->get('generaterestcrud.service');
        $service->setContainer($this->getContainer());
        $dto = $this->getContainer()->get('servicedto');
        $bundles = $this->getContainer()
            ->get('kernel')
            ->getBundles();
        $bundle = $input->getArgument('targetBundle');
        
        if (strstr($bundle, '\\')) {
            foreach (bundles as $b) {
                if ($b->getNamespace() == $bundle) {
                    $bundle = $b->getName();
                    break;
                }
            }
        } else 
            if (strstr($bundle, '@')) {
                $bundle = str_replace('@', '', $bundle);
            }
        
        foreach ($bundles as $n => $b) {
            if ($b->getName() == $bundle) {
                $bundle = $b->getName();
                $npBundle = $b->getNamespace();
                break;
            }
        }
        
        $bundleDir = $this->getContainer()
            ->get('kernel')
            ->locateResource('@' . $bundle);
        
        $rootEntity = $input->getArgument('rootEntity');
        $shortName = explode('\\', $rootEntity);
        $shortName = $shortName[count($shortName) - 1];
        
        if (strstr($rootEntity, '\\')) {
            $ent = $rootEntity;
        } else {
            $ent = $npBundle . '\\Entity\\' . $shortName;
            $rootEntity = null;
        }
        
        if (! class_exists($ent)) {
            $output->writeln("<bg=red;fg=white>" . str_pad("", 120, " ") . "</bg=red;fg=white>");
            $output->writeln("<bg=red;fg=white>" . str_pad("  Entidade inexistente: " . $ent, 120) . "</bg=red;fg=white>");
            $output->writeln("<bg=red;fg=white>" . str_pad("  Caso esteja executando o comando em um terminal POSIX, será necessário colocar no nome", 120) . "</bg=red;fg=white>");
            $output->writeln("<bg=red;fg=white>" . str_pad("  da entidade entre apóstrofes ('). Ex: '\Namespace\NomeDoBundle\Entity\Entidade'", 120) . "</bg=red;fg=white>");
            $output->writeln("<bg=red;fg=white>" . str_pad("", 120, " ") . "</bg=red;fg=white>");
            die();
        } else {
            $ent = new $ent();
        }
        
        $dto->query->set('BUNDLEDIR', $bundleDir);
        $dto->query->set('BUNDLENAMESPACE', $npBundle);
        $dto->query->set('BUNDLENAME', $bundle);
        $dto->query->set('ENTITYNAME', $shortName);
        $dto->query->set('ENTITYFULLNAME', $rootEntity);
        $dto->query->set('ENTITYNAMELOWER', strtolower($shortName));
        $dto->query->set('ATTRS', $ent->buildFullEmptyEntity());
        
        $service->setDto($dto);
        $service->generateCrud();
    }

    public function printBrasil($mensagem, $output)
    {
        $line = str_pad("", 120, " ");
        
        $msgs = explode("\n", $mensagem);
        
        $output->writeln("");
        $output->writeln("<bg=green>${line}</bg=green>");
        $output->writeln("<bg=yellow>${line}</bg=yellow>");
        $output->writeln("<bg=blue;fg=white>${line}</bg=blue;fg=white>");
        foreach ($msgs as $mensagem) {
            $output->writeln("<bg=blue;fg=white>" . str_pad('   ' . $mensagem, 120, " ") . "</bg=blue;fg=white>");
        }
        $output->writeln("<bg=blue;fg=white>${line}</bg=blue;fg=white>");
        $output->writeln("<bg=yellow>${line}</bg=yellow>");
        $output->writeln("<bg=green>${line}</bg=green>");
        $output->writeln("");
    }
}
