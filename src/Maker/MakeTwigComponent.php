<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Dependency\DependencyManager;
use Symfony\Bundle\MakerBundle\Dependency\Model\OptionalClassDependency;
use Symfony\Bundle\MakerBundle\Dependency\Model\RequiredClassDependency;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeTwigComponent extends AbstractMaker
{
    private DependencyManager $dependencyManager;

    public static function getCommandName(): string
    {
        return 'make:twig-component';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a twig (or live) component';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of your twig component (ie <fg=yellow>Notification</>)')
            ->addOption('live', null, InputOption::VALUE_NONE, 'Whether to create a live twig component (requires <fg=yellow>symfony/ux-live-component</>)')
        ;
    }

    public function configureComposerDependencies(DependencyManager $dependencyManager): void
    {
        // $this is a hack - we need the manager later in `interact()`
        $this->dependencyManager = $dependencyManager;

        $dependencyManager
            ->addDependency(new RequiredClassDependency(
                className: AsTwigComponent::class,
                composerPackage: 'symfony/ux-twig-component',
                preInstallMessage: 'This command requires the Symfony UX Twig Component Package.'
            ))
            ->addDependency(new OptionalClassDependency(
                className: AsLiveComponent::class,
                composerPackage: 'symfony/ux-live-component',
                preInstallMessage: 'The Symfony UX Live Component is needed to make this a live component.'
            ))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!$input->getOption('live')) {
            $input->setOption('live', $io->confirm('Make this a live component?', false));
        }

        if (!$input->getOption('live')) {
            return;
        }

        // @TODO - with the dependencyManager in `Command` -> we can't use it outside of configure dependencies.....
        $this->dependencyManager->installOptionalDependencies();
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $name = $input->getArgument('name');
        $live = $input->getOption('live');

        if ($live && !class_exists(AsLiveComponent::class)) {
            throw new \RuntimeException('You must install symfony/ux-live-component to create a live component (composer require symfony/ux-live-component)');
        }

        $factory = $generator->createClassNameDetails(
            $name,
            'Twig\\Components',
        );

        $shortName = Str::getShortClassName($factory->getShortName());

        $generator->generateClass(
            $factory->getFullName(),
            sprintf('%s/../Resources/skeleton/twig/%s', __DIR__, $live ? 'LiveComponent.tpl.php' : 'Component.tpl.php'),
            [
                'live' => $live,
            ]
        );
        $generator->generateTemplate(
            "components/{$shortName}.html.twig",
            sprintf('%s/../Resources/skeleton/twig/%s', __DIR__, 'component_template.tpl.php')
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->newLine();
        $io->writeln(" To render the component, use <fg=yellow><twig:{$shortName} /></>.");
        $io->newLine();
    }
}
