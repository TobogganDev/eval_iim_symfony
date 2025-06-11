<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Produit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un admin
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setLastname('Admin');
        $admin->setFirstname('Super');
        $admin->setPoints(10000);
        $admin->setActive(true);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password123'));
        $manager->persist($admin);

        // Créer un utilisateur normal
        $user = new User();
        $user->setEmail('user@test.com');
        $user->setLastname('User');
        $user->setFirstname('Normal');
        $user->setPoints(500);
        $user->setActive(true);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $manager->persist($user);

        // Créer des produits
        $categories = ['Électronique', 'Livres', 'Vêtements', 'Alimentation'];

        for ($i = 1; $i <= 10; $i++) {
            $produit = new Produit();
            $produit->setName('Produit ' . $i);
            $produit->setPrice(random_int(50, 500));
            $produit->setCategory($categories[array_rand($categories)]);
            $produit->setDescription('Description du produit ' . $i);
            $produit->setCreatedBy($admin);
            $manager->persist($produit);
        }

        $manager->flush();
    }
}
