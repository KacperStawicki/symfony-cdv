<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user'
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'User password')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Set user as admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getOption('email');
        $password = $input->getOption('password');
        $isAdmin = $input->getOption('admin');

        if (!$email) {
            $email = $io->ask('Please enter user email');
        }

        if (!$password) {
            $password = $io->askHidden('Please enter user password');
        }

        try {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $password)
            );

            if ($isAdmin) {
                $user->setRoles(['ROLE_ADMIN']);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success(sprintf('User %s was created successfully!', $email));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Could not create user: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
