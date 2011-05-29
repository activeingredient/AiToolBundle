<?php

/**
 * This file is part of the Ai Tool Bundle
 *
 * (c) 2011 Mark Brennand, ACTiVEiNGREDiENT
 * 
 */

namespace Ai\ToolBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Symfony\Bundle\DoctrineBundle\Mapping\MetadataFactory;

/**
 * Generate annotated entity and repository classes from metadata mapping information.
 *
 * This differs from the ai:generate:orm command only in some defaults
 *  for the EntityGenerator
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
      ->setDescription('Generate entity classes and method stubs from your mapping information')
      ->addArgument('name', InputArgument::REQUIRED, 'A bundle name, a namespace, or a class name')
      ->addOption('path', null, InputOption::VALUE_REQUIRED, 'The path where to generate entities when it cannot be guessed')
      ->addOption('no-backup', null, InputOption::VALUE_NONE, 'Do not backup existing entities files.')
      ->setHelp(<<<EOT
The <info>ai:generate:orm</info> command generates entity classes
and method stubs from your mapping information:

You have to limit generation of entities:

* To a bundle:

  <info>./app/console ai:generate:orm MyCustomBundle</info>

* To a single entity:

  <info>./app/console ai:generate:orm MyCustomBundle:User</info>
  <info>./app/console ai:generate:orm MyCustomBundle/Entity/User</info>

* To a namespace

  <info>./app/console ai:generate:orm MyCustomBundle/Entity</info>

If the entities are not stored in a bundle, and if the classes do not exist,
the command has no way to guess where they should be generated. In this case,
you must provide the <comment>--path</comment> option:

  <info>./app/console ai:generate:orm Blog/Entity --path=src/</info>

You should provide the <comment>--no-backup</comment> option if you dont mind to back up files
before to generate entities:

  <info>./app/console ai:generate:orm Blog/Entity --no-backup</info>

EOT
    );
  }
  
  protected function getEntityGenerator()
  {
    $entityGenerator = new EntityGenerator();
    $entityGenerator->setAnnotationPrefix('@Doctrine\\ORM\\Mapping\\');
    $entityGenerator->setGenerateAnnotations(true);
    $entityGenerator->setGenerateStubMethods(true);
    $entityGenerator->setRegenerateEntityIfExists(false);
    $entityGenerator->setUpdateEntityIfExists(true);
    $entityGenerator->setNumSpaces(2);
    return $entityGenerator;
  }
}