<?php
namespace SanSIS\DevelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListSchemaTablesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sansis:reverse:listschematables')
            ->setDescription('Lista os schemas disponíveis no banco de dados')
            ->addArgument(
                'schemaName',
                InputArgument::REQUIRED,
                'Nome do schema que tera as tabelas listadas.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $this->getContainer()->get('dbreverse.service');
        $dto = $this->getContainer()->get('servicedto');

        $dto->query->set('schema', $input->getArgument('schemaName'));
        $service->setDto($dto);

        $tables = $service->getSchemaTables();

        $msg = "    Tabelas disponiveis no schema \"" . $input->getArgument('schemaName') . "\" (" . count($tables) . ")";

        $this->printBrasil($msg, $output);

        $text = '';

        foreach ($tables as $table) {
            if ($table) {
                $text .= $table . "\n";
            }
        }

        $output->writeln($text);

        $this->printBrasil('', $output);
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
