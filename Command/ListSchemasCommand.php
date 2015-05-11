<?php
namespace SanSIS\DevelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListSchemasCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sansis:reverse:listschemas')
            ->setDescription('Lista os schemas disponíveis no banco de dados.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $this->getContainer()->get('dbreverse.service');
        $schemas = $service->getSchemas();

        $msg = "    Schemas disponiveis (" . count($schemas) . "): ";

        $this->printBrasil($msg, $output);

        $text = '';

        foreach ($schemas as $schema) {
            $text .= $schema . "\n";
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
