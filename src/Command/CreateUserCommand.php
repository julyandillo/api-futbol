<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crea un nuevo usuario',
    hidden: false
)]
class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    private UserPasswordHasherInterface $passwordHasher;

    private \Doctrine\Persistence\ObjectManager $em;

    public function __construct(UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine)
    {
        $this->passwordHasher = $passwordHasher;
        $this->em = $doctrine->getManager();

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setHelp('Crea un nuevo usuario')
            ->addOption(
                'admin',
                null,
                InputOption::VALUE_NONE | InputOption::VALUE_OPTIONAL,
                'indica que el usuario a crear sera un administrador'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            '',
            'Creación de un nuevo usuario',
            ''
        ]);

        $helper = $this->getHelper('question');

        do {
            $output->writeln('Usuario:');
            $questionUsuario = new Question('> ');
            $usuario = $helper->ask($input, $output, $questionUsuario);
            $output->writeln('');

            $usuarioExiste = $this->em->getRepository(User::class)->findOneBy(['usuario' => $usuario]);

            if ($usuarioExiste) {
                $output->writeln('Ya existe un usuario que ese nombre, elige otro!');
            }

        } while($usuarioExiste);

        $output->writeln('Contraseña:');
        $questionPassword = new Question('> ');
        $questionPassword->setHidden(true);
        $questionPassword->setHiddenFallback(true);
        $passwordPlainText = $helper->ask($input, $output, $questionPassword);
        $output->writeln('');

        $output->writeln('Email (opcional):');
        $questionEmail = new Question('> ');
        $email = $helper->ask($input, $output, $questionEmail);
        $output->writeln('');

        $questionAdministrador = new ConfirmationQuestion(
            '¿crear como administrador? (si|no) ',
            false,
            '/^(y|s)/i'
        );
        $questionAdministrador->setAutocompleterValues(['yes', 'no']);

        $roles = ['ROLE_USER'];
        if ($helper->ask($input, $output, $questionAdministrador)) {
            $roles[] = 'ROLE_ADMIN';
        }

        $nuevoUsuario = new User();
        $nuevoUsuario->setUsuario($usuario);
        $nuevoUsuario->setRoles($roles);

        $hasedPassword = $this->passwordHasher->hashPassword(
            $nuevoUsuario,
            $passwordPlainText
        );
        $nuevoUsuario->setPassword($hasedPassword);

        $nuevoUsuario->setEmail($email != null ? $email : '');

        $this->em->persist($nuevoUsuario);
        $this->em->flush();

        return Command::SUCCESS;
    }
}