<?php
namespace SanSIS\DevelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReverseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sansis:reverse:entities')
            ->setDescription('Lista os schemas disponíveis no banco de dados.')
            ->addArgument(
                'targetBundle',
                InputArgument::REQUIRED,
                'Namespace do Bundle no qual deve ser criado o Crud.'
            )
            ->addArgument(
                'schemaName',
                InputArgument::REQUIRED,
                'Nome do schema do banco de onde serao criadas as entidades.'
            )
            ->addArgument(
                'tables',
                InputArgument::IS_ARRAY,
                'Nome das tabelas para reversa (deixe em branco para todas as tabelas).'
            )
            ->addOption(
                'ignore-prefix',
                'ip',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Prefixos das tabelas a serem retirados das entidades',
                array('tb', 'Tb', 'TB');
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $help = "                   Gerador de Entidades a partir de reversa do banco de dados";

        $this->printBrasil($help, $output);

        $service = $this->getContainer()->get('dbreverse.service');
        $dto = $this->getContainer()->get('servicedto');
        $bundles = $this->getContainer()->get('kernel')->getBundles();
        $bundle = $input->getArgument('targetBundle');

        if (strstr($bundle, '\\')) {
            foreach (bundles as $b) {
                if ($b->getNamespace() == $bundle) {
                    $bundle = $b->getName();
                    break;
                }
            }
        } else if (strstr($bundle, '@')) {
            $bundle = str_replace('@', '', $bundle);
        }

        foreach ($bundles as $n => $b) {
            if ($b->getName() == $bundle) {
                $bundle = $b->getName();
                $npBundle = $b->getNamespace();
                break;
            }
        }

        $bundleDir = $this->getContainer()->get('kernel')->locateResource('@' . $bundle);

        $dto->query->set('bundleDir', $bundleDir);
        $dto->query->set('bundleName', $input->getArgument('targetBundle'));
        $dto->query->set('bundleNameSpace', $npBundle);
        $dto->query->set('schema', $input->getArgument('schemaName'));
        $dto->query->set('tables', $input->getArgument('tables'));
        $dto->query->set('ignore-prefix', $input->getOption('ignore-prefix'));

        $service->setDto($dto);
        $tables = $service->reverseEntities();

        $help = "ATENCAO!!!! A reversa de entidades da Doctrine precisa revisao manual apos a geracao!!!\n";
        $help .= "Verifique os seguintes itens:\n";
        $help .= " - ArrayCollections devem estar com a annotation innerEntity para identificacao o tipo de entidade que ela contem.\n";
        $help .= " - Relacionamentos ManyToMany nao sao gerados, a tabela associativa vira uma outra entidade.\n";
        $help .= " - Relacionamentos OneToOne sao gerados automaticamente como ManyToOne.\n";
        $help .= " - Relacionamentos OneToMany tambem nao sao gerados automaticamente, sendo necessario criar manualmente.";

        $this->printBrasil($help, $output);
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
