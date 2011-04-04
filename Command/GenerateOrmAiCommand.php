<?php

/**
 * This file is part of the Ai Tool Bundle
 *
 * (c) 2011 Mark Brennand, ACTiVEiNGREDiENT
 * 
 */

namespace Ai\ToolBundle\Command;

use Symfony\Bundle\DoctrineBundle\Command\GenerateEntitiesDoctrineCommand;

/**
 * Generate annotated entity and repository classes from metadata mapping information
 *
 * @author Mark Brennand <mark@activeingredient.com.au>
 * 
 */

class GenerateOrmAiCommand extends GenerateEntitiesDoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('ai:generate:orm')
            ->setDescription('Generate orm classes and method stubs from your mapping information.')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to initialize the entity or entities in.')
            ->addOption('entity', null, InputOption::VALUE_OPTIONAL, 'The entity class to initialize (shortname without namespace).')
            ->setHelp(<<<EOT
The <info>ai:generate:orm</info> command generates orm classes and method stubs from your mapping information:

You have to limit generation of entities to an individual bundle:

  <info>./app/console ai:generate:orm "AiCoreBundle"</info>

Alternatively, you can limit generation to a single entity within a bundle:

  <info>./app/console ai:generate:orm "AiCoreBundle" --entity="User"</info>

You have to specify the shortname (without namespace) of the entity you want to filter for.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundleName = $input->getArgument('bundle');
        $filterEntity = $input->getOption('entity');

        $foundBundle = $this->findBundle($bundleName);

        if ($metadatas = $this->getBundleMetadatas($foundBundle)) {
            
            # do entity classes
            $output->writeln(sprintf('Generating entities for "<info>%s</info>"', $foundBundle->getName()));
            $entityGenerator = $this->getEntityGenerator();

            foreach ($metadatas as $metadata) {
                if ($filterEntity && $metadata->reflClass->getShortName() !== $filterEntity) {
                    continue;
                }

                if (strpos($metadata->name, $foundBundle->getNamespace()) === false) {
                    throw new \RuntimeException(
                        "Entity " . $metadata->name . " and bundle don't have a common namespace, ".
                        "generation failed because the target directory cannot be detected.");
                }

                $output->writeln(sprintf('  > generating <comment>%s</comment>', $metadata->name));
                $entityGenerator->generate(array($metadata), $this->findBasePathForBundle($foundBundle));
            }
            
            # now do repository classes
            $output->writeln(sprintf('Generating entity repositories for "<info>%s</info>"', $foundBundle->getName()));
            $generator = new EntityRepositoryGenerator();
            
            foreach ($metadatas as $metadata) {
                if ($filterEntity && $filterEntity !== $metadata->reflClass->getShortname()) {
                    continue;
                }

                if ($metadata->customRepositoryClassName) {
                    if (strpos($metadata->customRepositoryClassName, $foundBundle->getNamespace()) === false) {
                        throw new \RuntimeException(
                            "Repository " . $metadata->customRepositoryClassName . " and bundle don't have a common namespace, ".
                            "generation failed because the target directory cannot be detected.");
                    }

                    $output->writeln(sprintf('  > <info>OK</info> generating <comment>%s</comment>', $metadata->customRepositoryClassName));
                    $generator->writeEntityRepositoryClass($metadata->customRepositoryClassName, $this->findBasePathForBundle($foundBundle));
                } else {
                    $output->writeln(sprintf('  > <error>SKIP</error> no custom repository for <comment>%s</comment>', $metadata->name));
                }
            }
        } else {
            throw new \RuntimeException("Bundle " . $bundleName . " does not contain any mapped entities.");
        }
    }
    
    protected function getEntityGenerator()
    {
        $entityGenerator = new EntityGenerator();
        $entityGenerator->setAnnotationPrefix("orm:");
        $entityGenerator->setGenerateAnnotations(true);
        $entityGenerator->setGenerateStubMethods(true);
        $entityGenerator->setRegenerateEntityIfExists(false);
        $entityGenerator->setUpdateEntityIfExists(true);
        $entityGenerator->setNumSpaces(2);
        return $entityGenerator;
    }
}